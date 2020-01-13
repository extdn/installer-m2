<?php

namespace ExtDN\Task;

class CacheStatus extends BaseBinMagento
{
    const CMD_NOTICE = '';
    const BIN_COMMAND = 'cache:status';

    protected $silent = true;
}
