<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class ReadCacheStatus extends \Robo\Task\BaseTask implements BuilderAwareInterface
{

    use \Robo\Common\BuilderAwareTrait;

    private $cacheTypes;

    public function __construct($cacheTypes)
    {
        $this->cacheTypes = $cacheTypes;
    }

    public function run()
    {

        $this->printTaskInfo('Reading current cache status.');

        try {
            /** @var \Robo\Result $result */
            $result = $this->collectionBuilder()->taskCacheStatus()->silent(true)->run();
            $currentCacheList = explode(PHP_EOL, $result->getMessage());
            $currentCacheList = array_filter(
                $currentCacheList,
                function ($element) {
                    list($type, $status) = explode(':', $element);
                    return ((bool)trim($status) && in_array(trim($type), $this->cacheTypes));
                }
            );
            $extractedEnabledCaches = [];
            foreach ($currentCacheList as $cacheLine) {
                list($type, $status) = explode(':', $cacheLine);
                $extractedEnabledCaches[trim($type)] = (bool)trim($status);
            }

            $data = [];
            $data['enabled-caches'] = $extractedEnabledCaches;
            $this->printTaskSuccess('[OK]');
            return Result::success($this, '[OK]', $data);
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }
}
