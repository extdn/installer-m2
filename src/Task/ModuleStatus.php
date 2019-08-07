<?php

namespace ExtDN\Task;

class ModuleStatus extends BaseBinMagento
{
    const CMD_NOTICE = '';
    const BIN_COMMAND = 'module:status';

    protected $silent = true;
}
