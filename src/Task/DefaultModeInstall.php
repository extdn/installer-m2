<?php

namespace ExtDN\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Result;

class DefaultModeInstall extends \Robo\Task\BaseTask implements BuilderAwareInterface
{
    use \Robo\Common\BuilderAwareTrait;

    protected $package;
    protected $template;
    protected $repoUrl;
    protected $path;
    protected $initialModules;

    public function __construct($composerPath, $template, $package, $modules, $url = null)
    {
        $this->path = $composerPath;
        $this->template = $template;
        $this->package = $package;
        $this->repoUrl = $url;
        $this->initialModules = $modules;
    }

    public function run()
    {
        $this->performInstallation();
        return Result::success($this);
    }

    protected function performInstallation()
    {
        $this->collectionBuilder()->taskInstallCode(
            $this->path,
            $this->template,
            $this->package,
            $this->repoUrl
        )->run();

        $this->collectionBuilder()->taskModuleEnable($this->initialModules)->run();
        $this->collectionBuilder()->taskSetupUpgrade()->run();
    }
}
