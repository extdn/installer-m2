<?php

namespace ExtDN\Task;

use Robo\Result;

class CheckMagentoRoot extends \Robo\Task\BaseTask
{

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        try {
            $this->checkRunningInMagento2Root();
            return Result::success($this);
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }

    private function checkRunningInMagento2Root()
    {
        $this->printTaskInfo('Checking that we are running in the correct folder.');
        if (!file_exists(getcwd() . '/app/etc/config.php')) {
            throw new \RuntimeException(
                'Could not find app/etc/config.php - please execute from the Magento root folder.'
            );
        }
        if (!file_exists(getcwd() . '/app/etc/env.php')) {
            throw new \RuntimeException(
                'Could not find app/etc/env.php - please execute from the Magento root folder.'
            );
        }
        $env = require getcwd() . '/app/etc/env.php';
        if (!isset($env['install']['date'])) {
            throw new \RuntimeException(
                'No installation date found in app/etc/env.php - please install Magento first.'
            );
        }
        if (!file_exists(getcwd() . '/composer.json')) {
            throw new \RuntimeException('Could not find composer.json - please execute from the Magento root folder.');
        }
        $this->printTaskSuccess('[OK]');
    }
}
