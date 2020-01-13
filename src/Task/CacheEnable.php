<?php

namespace ExtDN\Task;

class CacheEnable extends BaseBinMagento
{
    const CMD_NOTICE = 'Re-enabling caches';
    const BIN_COMMAND = 'cache:enable';

    private $cachesToEnable;

    public function __construct($cachesToEnable)
    {
        $this->cachesToEnable = $cachesToEnable;
    }

    protected function getCommand()
    {
        return static::BIN_COMMAND . $this->cachesToEnable;
    }
}
