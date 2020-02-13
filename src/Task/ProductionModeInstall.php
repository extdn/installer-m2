<?php

namespace ExtDN\Task;

class ProductionModeInstall extends DefaultModeInstall
{
    private $initialLocales;

    public function __construct($composerPath, $template, $package, $modules, $locales, $url = null)
    {
        $this->initialLocales = $locales;
        parent::__construct($composerPath, $template, $package, $modules, $url);
    }

    public function run()
    {
        $this->collectionBuilder()->taskEnablingDeveloperMode()->run();
        $this->performInstallation();
        $this->reenableProductionMode();
    }

    private function reenableProductionMode()
    {
        $this->printTaskInfo('Re-enabling Production Mode.');
        $localesToCompile = [];
        foreach ($this->initialLocales as $locale) {
            $localeKey = basename('/' . ltrim($locale, '/'));
            $localesToCompile[$localeKey] = $localeKey;
        }
        $commandArg = '--';
        foreach ($localesToCompile as $locale) {
            $commandArg .= ' ' . $locale;
        }

        $this->collectionBuilder()->taskStaticContentCompile($commandArg)->run();
        $this->collectionBuilder()->taskSetupDiCompile()->run();
        $this->collectionBuilder()->taskEnablingProductionMode()->run();

        $this->printTaskSuccess('[OK]');
    }
}
