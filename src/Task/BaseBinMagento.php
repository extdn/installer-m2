<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

abstract class BaseBinMagento extends \Robo\Task\BaseTask implements BuilderAwareInterface
{

    use \Robo\Common\BuilderAwareTrait;

    const CMD_NOTICE = '';
    const BIN_COMMAND = '';

    protected $silent = false;

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        if (!empty(static::CMD_NOTICE)) {
            $this->printTaskInfo(static::CMD_NOTICE);
        }

        try {
            //TODO maybe use CommandStack instead
            $result = $this->collectionBuilder()
                           ->taskExec($this->constructBinMagentoCommand($this->getCommand()))
                           ->silent($this->silent)
                           ->run();
            $this->printTaskSuccess('[OK]');
            return $result;
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }

    public function silent($arg)
    {
        $this->silent = $arg;
        return $this;
    }

    protected function getCommand()
    {
        return static::BIN_COMMAND;
    }

    protected function constructBinMagentoCommand($cmd)
    {
        $this->printTaskDebug('/usr/bin/env php -d memory_limit=-1 -f bin/magento ' . $cmd);
        return '/usr/bin/env php -d memory_limit=-1 -f bin/magento ' . $cmd;
    }
}
