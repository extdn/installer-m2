<?php

namespace ExtDN\Task;

use Robo\Result;

class ModuleEnable extends BaseBinMagento
{
    const CMD_NOTICE = 'Enabling new modules.';
    const BIN_COMMAND = 'module:enable --clear-static-content';

    private $modulesToEnable;
    private $initialModules;

    public function __construct($initialModules)
    {
        $this->initialModules = $initialModules;
    }

    protected function getCommand()
    {
        return static::BIN_COMMAND . $this->modulesToEnable;
    }

    /**
     * Enable new modules (that weren't present when installer started)
     */
    public function run()
    {
        $this->printTaskInfo('Checking for new module(s).');
        $result = $this->collectionBuilder()->taskModuleStatus()->silent(true)->run();
        $currentModuleList = explode(PHP_EOL, $result->getMessage());
        $currentModuleList = array_filter(
            $currentModuleList,
            function ($element) {
                return (!in_array($element, ['', 'None', 'List of disabled modules:', 'List of enabled modules:']));
            }
        );

        $moduleDiff = array_diff_key(array_flip($currentModuleList), $this->initialModules);
        if (empty($moduleDiff)) {
            return Result::error($this, 'No new modules detected.');
        }
        $this->modulesToEnable = '';
        foreach ($moduleDiff as $module => $value) {
            $this->modulesToEnable .= ' ' . $module;
        }
        // TODO: it's possible that the to-installed extension was previously installed
        // but continues to exist disabled in app/etc/config.php
        // maybe we need a fallback mode to search for registration.php and find the Magento module name that way
        // however that would fail in case of metapackage installs
        // or we could override config module status = 0
        return parent::run();
    }
}
