<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class CheckComposerIssues extends \Robo\Task\BaseTask implements BuilderAwareInterface
{
    use \Robo\Common\BuilderAwareTrait;

    private $path;

    public function __construct($composerPath)
    {
        $this->path = $composerPath;
    }

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        $this->printTaskInfo('Checking for common Composer issues');
        try {
            $this->printTaskInfo('Validating composer.json');
            $result = $this->collectionBuilder()->taskComposerValidate($this->path)->run();

            if (!$result->wasSuccessful()) {
                return Result::error($this, $result->getMessage(), $result->getData());
            }
            // TODO some validation here that this is indeed a Magento based project
            // I have seen more than one person overwrite their root composer.json file from an extension...

            $this->printTaskInfo('Checking for outstanding Composer changes');
            $result = $this->collectionBuilder()->taskComposerUpdate($this->path)->option('--lock')
                           ->option('--dry-run')->run();

            // TODO should add a non-interactive flag here to answer y
            if (!$result->wasSuccessful()) {
                $answer = $this->confirm(
                    'Found some issues. Continuing could apply package updates. Continue?'
                );
                if (!$answer) {
                    $this->exitWithError(
                        'Stopped installation as requested',
                        [
                            $this->path . ' update'
                        ]
                    );
                }
            }
            $this->printTaskSuccess('[OK]');
            return Result::success($this);
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }
}
