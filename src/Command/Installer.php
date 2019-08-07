<?php

namespace ExtDN\Command;

use Consolidation\AnnotatedCommand\CommandError;

class Installer extends \Robo\Tasks
{

    use \ExtDN\Task\All;

    private $options;
    private $mageEnv;
    private $config;
    private $initialLocales;
    private $initialModules;
    private $initialDeployMode;
    private $initialMaintenance;

    public function getOption($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        return '';
    }

    /**
     * @param       $package
     * @param array $options
     *
     * @return CommandError|int
     */
    public function install($package, $options = ['repo-url' => null, 'template'=>'pre-configured'])
    {
        $this->say($this->getBanner());
        $this->options = $options;
        $this->options['package'] = $package;

        $this->stopOnFail(true);

        try {
            $this->say('Running ExtDN Installer ');

            # TODO maybe use a collection instead to run multiple tasks chained together
            # check correct directory
            $this->taskCheckMagentoRoot()->run();
            $this->loadMageEnv();

            # TODO check environment? POSIX? ie enough memory?
            # TODO check for snowdog frontools?
            # TODO checks for broken frontends

            # check recommended user
            # check file permissions (write)
            $this->taskCheckPermissions()->run();

            # log current locales
            $result = $this->taskReadCurrentLocales()->setMode($this->mageEnv['MAGE_MODE'])->run();
            if (isset($result->getData()['locales'])) {
                $this->initialLocales = $result->getData()['locales'];
            } else {
                $this->initialLocales = [];
            }

            # log current modules from config.php
            $result = $this->taskReadExistingModules()->run();
            $this->initialModules = $result->getData()['modules'];

            # check current Magento mode - confirm proceed for production
            $this->checkMagentoMode();

            # TODO check current maintenance mode
            # TODO check for mismatched php versions
            # TODO check for missing auth (maybe restore from var/composer_home if available)
            # TODO we could backup composer.lock to allow a rollback

            # check for unfinished composer operations
            $this->taskCheckComposerIssues($this->getComposerPath())->run();

            # TODO question can we do a dry-run option for the complete install?
            $this->installNewModule();

            $this->taskDisableMaintenance()->run();

            # TODO post install check (for example can we access URL, could be tricky as websites are often
            # not configured to access themselves via DNS
            # TODO delete self after running? (optional)
            $this->say(
                sprintf(
                    '[OK] Installation of package %s successfully completed', $this->getOption('package')
                )
            );
        } catch (\Exception $e) {
            return new CommandError($e->getMessage());
        }
        return 0;
    }

    private function loadMageEnv()
    {
        $this->mageEnv = require getcwd() . '/app/etc/env.php';
    }

    private function exitWithError($msg, $suggestions = [])
    {
        $this->out($msg);
        if (!empty($suggestions)) {
            $this->out('');
            $this->out('Potential Options:');
            $this->out(implode(PHP_EOL, $suggestions));
        }
        throw new \RuntimeException('Exited with error');
    }

    private function out($msg)
    {
        //TODO we could log to file as well
        $this->say($msg);
    }

    private function getComposerPath()
    {
        return 'vendor/composer/composer/bin/composer';
    }

    private function checkMagentoMode()
    {
        $this->out('Confirming current Magento deployment mode.');
        if (!in_array($this->mageEnv['MAGE_MODE'], ['developer', 'production', 'default'])) {
            $this->exitWithError('Magento deployment mode is none of developer, production, default');
        }

        $this->initialDeployMode = $this->mageEnv['MAGE_MODE'];
        // TODO should add a non-interactive flag here to answer y
        if ($this->initialDeployMode === 'production') {
            $answer = $this->confirm(
                'Magento is currently in production mode. Continuing will temporarily place the store into developer mode. Continue?'
            );
            if (!$answer) {
                $this->exitWithError(
                    'Stopped installation as requested',
                    [
                        'Install in a development environment and use your standard deployment process.',
                        '',
                        'Switch to developer mode manually before re-running the installer.',
                        '/usr/bin/env php -f bin/magento deploy:mode:set developer',
                        '',
                        'Re-run this installer with the above question answered yes.'
                    ]
                );
            }
        }
        $this->out('[OK]');
    }

    private function installNewModule()
    {
        switch ($this->initialDeployMode) {
            case 'default':
                $this->taskDefaultModeInstall(
                    $this->getComposerPath(),
                    $this->getOption('template'),
                    $this->getOption('package'),
                    $this->initialModules,
                    $this->getOption('repo-url')
                )->run();
                break;
            case 'developer':
                $this->taskDeveloperModeInstall(
                    $this->getComposerPath(),
                    $this->getOption('template'),
                    $this->getOption('package'),
                    $this->initialModules,
                    $this->getOption('repo-url')
                )->run();
                break;
            case 'production':
                $this->taskProductionModeInstall(
                    $this->getComposerPath(),
                    $this->getOption('template'),
                    $this->getOption('package'),
                    $this->initialModules,
                    $this->initialLocales,
                    $this->getOption('repo-url')
                )->run();
                break;
        }
    }

    private function getBanner()
    {
        return <<<EOF
        
 ______      _   _____  _   _   _____           _        _ _           
|  ____|    | | |  __ \| \ | | |_   _|         | |      | | |          
| |__  __  _| |_| |  | |  \| |   | |  _ __  ___| |_ __ _| | | ___ _ __ 
|  __| \ \/ / __| |  | | . ` |   | | | '_ \/ __| __/ _` | | |/ _ \ '__|
| |____ >  <| |_| |__| | |\  |  _| |_| | | \__ \ || (_| | | |  __/ |   
|______/_/\_\\__|_____/|_| \_| |_____|_| |_|___/\__\__,_|_|_|\___|_|   
EOF;
    }
}
