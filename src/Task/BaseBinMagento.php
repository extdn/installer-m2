<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class BaseBinMagento extends \Robo\Task\BaseTask implements BuilderAwareInterface
{

    use \Robo\Common\BuilderAwareTrait;
    use \Robo\Common\ConfigAwareTrait;

    const CMD_NOTICE = '';
    const BIN_COMMAND = '';

    protected $silent = false;

    protected static function configPrefix()
    {
        return 'task.ExtDN.BaseBinMagento.';
    }

    private static function getClassKey($key)
    {
        $configPrefix = static::configPrefix();                            // task.ExtDN.BaseBinMagento.
        $configPostFix = static::configPostfix();                          // .settings
        return sprintf('%s%s.%s', $configPrefix, $configPostFix, $key);
    }

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
        $phpBin = $this->getConfigValue('php-bin');
        $this->printTaskDebug($phpBin . ' -d memory_limit=-1 -f bin/magento ' . $cmd);
        return $phpBin .' -d memory_limit=-1 -f bin/magento ' . $cmd;
    }
}
