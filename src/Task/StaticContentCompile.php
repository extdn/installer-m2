<?php

namespace ExtDN\Task;

class StaticContentCompile extends BaseBinMagento
{
    const CMD_NOTICE = '.';
    const BIN_COMMAND = 'setup:static-content:deploy --force ';

    private $localesToEnable;

    public function __construct($localesToEnable)
    {
        $this->localesToEnable = $localesToEnable;
    }

    protected function getCommand()
    {
        return static::BIN_COMMAND . $this->localesToEnable;
    }
}
