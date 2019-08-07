<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class ReadExistingModules extends \Robo\Task\BaseTask implements BuilderAwareInterface
{

    use \Robo\Common\BuilderAwareTrait;

    public function run()
    {

        $this->printTaskInfo('Reading current module list.');

        try {
            $config = require getcwd() . '/app/etc/config.php';
            /** @var \Robo\Result $result */

            $result = $this->collectionBuilder()->taskModuleStatus()->silent(true)->run();
            $currentModuleList = explode(PHP_EOL, $result->getMessage());
            $currentModuleList = array_filter(
                $currentModuleList,
                function ($element) {
                    return (!in_array($element, ['', 'None', 'List of disabled modules:', 'List of enabled modules:']));
                }
            );
            $this->printTaskInfo('Checking for autoloaded modules not listed in app/etc/config.php');
            $moduleDiff = array_diff_key(array_flip($currentModuleList), $config['modules']);
            if (!empty($moduleDiff)) {
                return Result::error(
                    $this,
                    sprintf(
                        'Module mismatch for %s between app/etc/config.php and module:status detected.',
                        implode(',', array_keys($moduleDiff))
                    )
                );
            }

            $data = [];
            $data['modules'] = $config['modules'];
            $this->printTaskSuccess('[OK]');
            return Result::success($this, '[OK]', $data);
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }

}
