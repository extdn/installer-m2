#!/usr/bin/env php
<?php
/*
 * (c) Kristof Ringleff, ExtDN <kristof@fooman.co.nz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (PHP_SAPI !== 'cli') {
    echo 'The ExtDN installer must be run as a CLI application';
    exit(1);
}

interface ExtDN_InstallModeInterface
{
    public function perform();
}

class ExtDN_DefaultModeInstall implements ExtDN_InstallModeInterface
{
    private $installer;

    public function __construct(ExtDN_Installer $installer)
    {
        $this->installer = $installer;
    }

    public function perform()
    {
        $this->installer->installCode();
        $this->installer->enableJustAddedModules();
        $this->installer->runDatabaseSetup();
    }
}

class ExtDN_DeveloperModeInstall extends ExtDN_DefaultModeInstall
{

}

class ExtDN_ProductionModeInstall implements ExtDN_InstallModeInterface
{
    private $installer;

    public function __construct(ExtDN_Installer $installer)
    {
        $this->installer = $installer;
    }

    public function perform()
    {
        $this->installer->enableDeveloperMode();

        $devModeInstall = new ExtDN_DeveloperModeInstall($this->installer);
        $devModeInstall->perform();

        $this->installer->reenableProductionMode();
    }
}

class ExtDN_Installer
{
    const VERSION = '1.0.0';
    private $options;
    private $env;
    private $config;
    private $initialLocales;
    private $initialModules;
    private $initialDeployMode;
    private $initialMaintenance;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function getOption($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        $this->exitWithError(sprintf('Option with key %s is not available.', $key));
    }

    public function run()
    {
        try {
            $this->out('Running ExtDN Installer ' . self::VERSION);

            # check correct directory
            $this->checkRunningInMagento2Root();

            # TODO check environment? POSIX? ie enough memory?
            # TODO check for snowdog frontools?

            # check recommended user
            $this->checkCurrentUserVsExistingFiles();

            # check file permissions (write)
            $this->checkForSufficientPermissions();

            # log current locales
            $this->outputExistingMaterialisedLocales();

            # log current modules from config.php
            $this->saveExistingModules();

            # check current Magento mode - confirm proceed for production
            $this->checkMagentoMode();

            # TODO check current maintenance mode
            # TODO check for mismatched php versions
            # TODO check for missing auth (maybe restore from var/composer_home if available)

            # check for unfinished composer operations
            $this->checkOutstandingComposerChanges();

            # TODO question can we do a dry-run option for the complete install?

            $this->installNewModule();

            $this->disableMaintenance();

            # TODO post install check (for example can we access URL, could be tricky as websites are often
            # not configured to access themselves via DNS
            # TODO delete self after running? (optional)
            $this->out(sprintf('[OK] Installation of package %s successfully completed', $this->getOption('package')) . PHP_EOL, "\033[32m");

        } catch (\Exception $e) {
            return 1;
        }
        return 0;
    }

    private function exitWithError($msg, $suggestions = [])
    {
        $this->out($msg, "\033[31m");
        if (!empty($suggestions)) {
            $this->out('');
            $this->out('Potential Options:');
            $this->out(implode(PHP_EOL, $suggestions), "\033[93m");
        }
        throw new \RuntimeException('Can\'t continue due to error');
    }

    private function out($msg, $colour = "\033[0m")
    {
        echo $colour . $msg . "\033[0m". PHP_EOL;
    }

    private function askUserQuestion($msg, $options)
    {
        $options = array_map('strtolower', $options);
        do {
            $this->out(sprintf('%s (%s)', $msg, implode('/', $options)));
            $stdin = fopen('php://stdin', 'r');

            $response = strtolower(fgetc($stdin));
        } while (!in_array($response, $options));

        return $response;
    }

    private function checkRunningInMagento2Root()
    {
        $this->out('Checking that we are running in the correct folder.');
        if (!file_exists(getcwd() . '/app/etc/config.php')) {
            $this->exitWithError('Could not find app/etc/config.php - please execute from the Magento root folder.');
        }
        if (!file_exists(getcwd() . '/app/etc/env.php')) {
            $this->exitWithError('Could not find app/etc/env.php - please execute from the Magento root folder.');
        }
        $this->env = require getcwd() . '/app/etc/env.php';
        if (!isset($this->env['install']['date'])) {
            $this->exitWithError('No installation date found in app/etc/env.php - please install Magento first.');
        }
        if (!file_exists(getcwd() . '/composer.json')) {
            $this->exitWithError('Could not find composer.json - please execute from the Magento root folder.');
        }
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function checkCurrentUserVsExistingFiles()
    {
        $this->out('Checking current user.');
        if (!function_exists('posix_getpwuid')) {
            $this->exitWithError('Function posix_getpwuid() not available.');
        }
        $currentProcessUserId = posix_geteuid();
        if (fileowner(getcwd() . '/composer.json') !== $currentProcessUserId) {
            $this->exitWithError(
                sprintf(
                    'Current user: %s [%s] is different to file owner of composer.json: %s [%s]',
                    posix_getpwuid($currentProcessUserId)['name'],
                    $currentProcessUserId,
                    posix_getpwuid(fileowner(getcwd() . '/composer.json'))['name'],
                    fileowner(getcwd() . '/composer.json')
                ),
                $this->getFileOwnerShipSuggestions()
            );
        }

        if (fileowner(getcwd() . '/vendor') !== $currentProcessUserId) {
            $this->exitWithError(
                sprintf(
                    'Current user: %s [%s] is different to file owner of /vendor: %s [%s]',
                    posix_getpwuid($currentProcessUserId)['name'],
                    $currentProcessUserId,
                    posix_getpwuid(fileowner(getcwd() . '/vendor'))['name'],
                    fileowner(getcwd() . '/vendor')
                ),
                $this->getFileOwnerShipSuggestions()
            );
        }
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function checkForSufficientPermissions()
    {
        $this->out('Checking file ownership.');
        $output = [];
        //TODO: I have come across git files as read-only which should be okay
        exec('find . ! -writable', $output, $return);

        if ($return !== 0) {
            $this->exitWithError(
                'Not all files are writable for the current user', $this->getFileOwnerShipSuggestions()
            );
        }

        if (!empty($output)) {
            $this->exitWithError(
                'The following files are not writable for the current user' . PHP_EOL . implode(PHP_EOL, $output),
                $this->getFileOwnerShipSuggestions()
            );
        }
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function getFileOwnerShipSuggestions()
    {
        $currentProcessUserId = posix_geteuid();
        return [
            '* Change your current user to the file owner:',
            sprintf('su - %s -s /bin/sh', posix_getpwuid($currentProcessUserId)['name']),
            '',
            '* Change the file ownership to the current user:',
            sprintf('sudo chown %s -R %s', posix_getpwuid($currentProcessUserId)['name'], getcwd()),
            '',
            '* Change the file permissions for the current user:',
            sprintf('chmod +w -R %s', getcwd()),
            ''
        ];
    }

    private function outputExistingMaterialisedLocales()
    {
        $this->out('Locales');
        if ($this->env['MAGE_MODE'] === 'developer' || $this->env['MAGE_MODE'] === 'default') {
            $this->out('Created on demand in developer and default modes.');
        } else {
            exec('find pub/static -maxdepth 4 -mindepth 4 -type d', $this->initialLocales);
            $this->out(
                implode(PHP_EOL, $this->initialLocales)
            );
        }

        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function saveExistingModules()
    {
        $this->out('Reading current module list.');
        $this->config = require getcwd() . '/app/etc/config.php';
        $this->initialModules = $this->config['modules'];
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function checkMagentoMode()
    {
        $this->out('Confirming current Magento deployment mode.');
        if (!in_array($this->env['MAGE_MODE'], ['developer', 'production', 'default'])) {
            $this->exitWithError('Magento deployment mode is none of developer, production, default');
        }

        $this->initialDeployMode = $this->env['MAGE_MODE'];
        // TODO should add a non-interactive flag here to answer y
        if ($this->initialDeployMode === 'production') {
            $answer = $this->askUserQuestion(
                'Magento is currently in production mode. Continuing will temporarily place the store into maintenance mode. Continue?',
                ['y', 'n']
            );
            if ($answer !== 'y') {
                $this->exitWithError(
                    'Stopped installation as requested',
                    [
                        'Install in a development environment and use your standard deployment process.',
                        '',
                        'Switch to developer mode manually before re-running the installer.',
                        '/usr/bin/env php -f bin/magento deploy:mode:set developer',
                        '',
                        'Re-run this installer with the maintenance question answered yes.'
                    ]
                );
            }
        }
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function checkOutstandingComposerChanges()
    {
        $this->out('Validating composer.json');
        $output = [];
        exec($this->constructComposerCommand('validate'), $output, $return);
        if ($return !== 0) {
            $this->exitWithError('composer.json validation failed.');
        }
        $this->out('[OK]' . PHP_EOL, "\033[32m");
        // TODO some validation here that this is indeed a Magento based project
        // I have seen more than one person overwrite their root composer.json file from an extension...

        $this->out('Checking for outstanding Composer changes');
        $output = [];
        exec($this->constructComposerCommand('update --lock --dry-run'), $output, $return);

        // TODO should add a non-interactive flag here to answer y
        if ($return !== 0) {
            $this->out(implode(PHP_EOL, $output));
            $answer = $this->askUserQuestion(
                'Found some issues. Continuing could apply package updates. Continue?',
                ['y', 'n']
            );
            if ($answer !== 'y') {
                $this->exitWithError(
                    'Stopped installation as requested',
                    [
                        $this->constructComposerCommand('update')
                    ]
                );
            }
        }

        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    public function installCode()
    {
        $this->out('Installing module code.' . PHP_EOL);
        $output = [];
        switch (strtolower($this->getOption('template'))) {
            # TODO other installation modes / vendor templates? composer zip artifact
            case 'marketplace':
                //TODO check keys
            case 'packagist':
            case 'pre-configured':
                //works for other pre-configured composer repositories too
                exec($this->constructComposerCommand('require ' . $this->getOption('package')), $output, $return);
                if ($return !== 0) {
                    $this->exitWithError('Composer require failed with ' . implode(PHP_EOL, $output));
                }
                break;
            default:
                //map template name to vendor with extra repo
                exec(
                    $this->constructComposerCommand(
                        sprintf(
                            'config repositories.%s composer %s',
                            strtolower($this->getOption('template')),
                            $this->getOption('repo-url')
                        )
                    )
                );
                exec($this->constructComposerCommand('require ' . $this->getOption('package')), $output, $return);
                if ($return !== 0) {
                    $this->exitWithError('Composer require failed with ' . implode(PHP_EOL, $output));
                }
                break;
        }
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    /**
     * Enable new modules (that weren't present when installer started)
     */
    public function enableJustAddedModules()
    {
        $this->out('Enabling new module.' . PHP_EOL);
        $currentModuleList = [];
        exec($this->constructBinMagentoCommand('module:status'), $currentModuleList);
        $currentModuleList = array_filter(
            $currentModuleList,
            function ($element) {
                return (!in_array($element, ['', 'None', 'List of disabled modules:', 'List of enabled modules:']));
            }
        );
        $moduleDiff = array_diff_key(array_flip($currentModuleList), $this->initialModules);
        if (empty($moduleDiff)) {
            $this->exitWithError('No new modules detected.');
        }
        $modulesToEnable = '';
        foreach ($moduleDiff as $module => $value) {
            $modulesToEnable .= ' ' . $module;
        }
        // TODO: it's possible that the to-installed extension was previously installed but continues to exist disabled in app/etc/config.php
        // maybe we need a fallback mode to search for registration.php and find the Magento module name that way
        // however that would fail in case of metapackage installs
        // or we could override config module status = 0
        exec($this->constructBinMagentoCommand('module:enable --clear-static-content' . $modulesToEnable), $output);
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    public function runDatabaseSetup()
    {
        $this->out('Running Database Setup.' . PHP_EOL);
        exec($this->constructBinMagentoCommand('setup:upgrade'));
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    public function enableDeveloperMode()
    {
        $this->out('Enabling Developer Mode.' . PHP_EOL);
        exec($this->constructBinMagentoCommand('deploy:mode:set developer'));
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    public function reenableProductionMode()
    {
        $this->out('Reenabling Production Mode.' . PHP_EOL);
        $localesToCompile = [];
        foreach ($this->initialLocales as $locale) {
            $localeKey = basename('/'.ltrim($locale,'/'));
            $localesToCompile[$localeKey] = $localeKey;
        }
        $commandArg ='';
        foreach ($localesToCompile as $locale) {
            $commandArg .= ' --language '.$locale;
        }

        exec($this->constructBinMagentoCommand('setup:static-content:deploy --force '.$commandArg));
        exec($this->constructBinMagentoCommand('setup:di:compile'));
        exec($this->constructBinMagentoCommand('deploy:mode:set production --skip-compilation'));
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function disableMaintenance()
    {
        $this->out('Disabling Maintenance Mode.' . PHP_EOL);
        //TODO check against initial state
        exec($this->constructBinMagentoCommand('maintenance:disable'));
        $this->out('[OK]' . PHP_EOL, "\033[32m");
    }

    private function constructComposerCommand($cmd)
    {
        return '/usr/bin/env php -d memory_limit=-1 -f vendor/composer/composer/bin/composer -- ' . $cmd;
    }

    private function constructBinMagentoCommand($cmd)
    {
        //TODO maybe in verbose mode only?
        $this->out('/usr/bin/env php -f bin/magento ' . $cmd . PHP_EOL);
        return '/usr/bin/env php -f bin/magento ' . $cmd;
    }

    private function installNewModule()
    {
        switch ($this->initialDeployMode) {
            case 'default':
                $installer = new ExtDN_DefaultModeInstall($this);
                $installer->perform();
                break;
            case 'developer':
                $installer = new ExtDN_DeveloperModeInstall($this);
                $installer->perform();
                break;
            case 'production':
                $installer = new ExtDN_ProductionModeInstall($this);
                $installer->perform();
                break;
        }
    }

}

function execute()
{
    $longopts = [
        'package:',
        'template::',
        'repo-url::',
        'help',
        'non-interactive'
    ];
    $options = getopt('', $longopts);

    if (isset($options['help'])) {
        displayHelp();
        exit(0);
    }
    if (!isset($options['package'])) {
        displayHelp();
        exit(0);
    }
    if (!isset($options['template'])) {
        $options['template'] = 'pre-configured';
    }
    $main = new ExtDN_Installer($options);
    exit($main->run());
}

/**
 * displays the help
 */
function displayHelp()
{
    echo <<<EOF

        ExtDN Installer
        ------------------
        Options
        --help               this help
        --non-interactive    TODO should answer yes to all questions
        --package            required Composer package name of module
        --template           optional use installation template [default: pre-configured repositories]
        --repo-url           optional

EOF;
}

execute();
