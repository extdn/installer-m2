<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class ReadCurrentLocales extends \Robo\Task\BaseTask implements BuilderAwareInterface
{

    use \Robo\Common\BuilderAwareTrait;

    private $mode;

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function run()
    {

        $this->printTaskInfo('Remembering materialised Locales');
        if ($this->mode === 'developer' || $this->mode === 'default') {
            $this->printTaskSuccess('Created on demand in developer and default modes.');
            return Result::success($this);
        }
        if (!$this->mode === 'production') {
            $this->printTaskError('Mode not set or unknown type.');
            return Result::error($this, 'Mode not set or unknown type.');
        }
        try {
            /** @var \Robo\Result $result */
            $result = $this->collectionBuilder()
                           ->taskExec('find pub/static -maxdepth 4 -mindepth 4 -type d')
                           ->silent(true)
                           ->run();
            $this->printTaskSuccess('[OK]');
            $data = [];
            $data['locales'] = explode(PHP_EOL, $result->getMessage());
            return Result::success($this, '[OK]', $data);
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }
}
