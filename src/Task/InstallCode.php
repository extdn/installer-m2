<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class InstallCode extends \Robo\Task\BaseTask implements BuilderAwareInterface
{

    use \Robo\Common\BuilderAwareTrait;

    private $package;
    private $template;
    private $repoUrl;
    private $path;

    public function __construct($composerPath, $template, $package, $url = null)
    {
        $this->path = $composerPath;
        $this->template = $template;
        $this->package = $package;
        $this->repoUrl = $url;
    }

    public function run()
    {
        $this->printTaskInfo('Installing module code.');
        switch (strtolower($this->template)) {
            case 'github':
                $this->collectionBuilder()->taskComposerConfig($this->path)->repository(
                    strtolower($this->package),
                    $this->repoUrl,
                    'vcs'
                )->run();
                $result = $this->collectionBuilder()->taskComposerRequire($this->path)->dependency($this->package)->run();
                break;
                //TODO other installation modes / vendor templates? composer zip artifact
            case 'marketplace':
                //TODO check keys
            case 'packagist':
            case 'pre-configured':
                //works for other pre-configured composer repositories too
                $result = $this->collectionBuilder()->taskComposerRequire($this->path)->dependency($this->package)->run();
                break;
            default:
                //map template name to vendor with extra repo
                $this->collectionBuilder()->taskComposerConfig($this->path)->repository(
                    strtolower($this->template),
                    $this->repoUrl,
                    'composer'
                )->run();
                $result = $this->collectionBuilder()->taskComposerRequire($this->path)->dependency($this->package)->run();
                break;
        }
        if (!$result->wasSuccessful()) {
            return Result::error($this, 'Composer require failed with '.$result->getMessage());
        }
        $this->printTaskSuccess('[OK]');
        return Result::success($this);
    }
}
