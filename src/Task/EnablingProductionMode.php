<?php

namespace ExtDN\Task;

class EnablingProductionMode extends BaseBinMagento
{
    const CMD_NOTICE = '';
    const BIN_COMMAND = 'deploy:mode:set production --skip-compilation';
}
