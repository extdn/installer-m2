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
            if (!$this->isMagentoProject()) {
                return Result::error($this, 'This does not look like a supported Magento project.');
            }

            $result = $this->collectionBuilder()->taskComposerValidate($this->path)->run();

            if (!$result->wasSuccessful()) {
                return Result::error($this, $result->getMessage(), $result->getData());
            }

            $this->printTaskInfo('Checking for outstanding Composer changes');
            $result = $this->collectionBuilder()->taskComposerUpdate($this->path)->option('--lock')
                           ->option('--dry-run')->run();

            // TODO should add a non-interactive flag here to answer y
            if (!$result->wasSuccessful()) {
                $answer = $this->confirm(
                    'Found some issues. Continuing could apply package updates. Continue?'
                );
                if (!$answer) {
                    return Result::error($this, 'Stopped installation as requested');
                }
            }
            $this->printTaskSuccess('[OK]');
            return Result::success($this);
        } catch (\Exception $e) {
            return Result::fromException($this, $e);
        }
    }

    private function isMagentoProject()
    {
        $this->printTaskInfo('Checking for supported Magento projects');
        if (!file_exists(getcwd() . '/composer.json')) {
            return false;
        }
        // I have seen more than one person overwrite their root composer.json file from an extension...
        $composerContent = json_decode(file_get_contents(getcwd() . '/composer.json'), true);

        return $this->isClonedMagentoRepo($composerContent)
            || $this->hasMagentoInComposerDependencies($composerContent);
    }

    private function isClonedMagentoRepo($composerContent)
    {
        return $composerContent['name'] === 'magento/magento2ce';
    }

    private function hasMagentoInComposerDependencies($composerContent)
    {
        $supportedMagentos = [
            'magento/product-community-edition',
            'magento/product-enterprise-edition',
            'magento/magento-cloud-metapackage'
        ];
        foreach ($composerContent['require'] as $dependency => $version) {
            if (in_array($dependency, $supportedMagentos)) {
                return true;
            }
        }
        return false;
    }
}
