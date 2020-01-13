<?php

namespace ExtDN\Task;

trait All
{
    protected function taskCheckComposerIssues($path)
    {
        return $this->task(CheckComposerIssues::class, $path);
    }

    protected function taskCheckMagentoRoot()
    {
        return $this->task(CheckMagentoRoot::class);
    }

    protected function taskCheckPermissions()
    {
        return $this->task(CheckPermissions::class);
    }

    protected function taskDefaultModeInstall($composerPath, $template, $package, $modules, $url = null)
    {
        return $this->task(DefaultModeInstall::class, $composerPath, $template, $package, $modules, $url);
    }

    protected function taskDeveloperModeInstall($composerPath, $template, $package, $modules, $url = null)
    {
        return $this->task(DeveloperModeInstall::class, $composerPath, $template, $package, $modules, $url);
    }

    protected function taskProductionModeInstall($composerPath, $template, $package, $modules, $locales, $url = null)
    {
        return $this->task(ProductionModeInstall::class, $composerPath, $template, $package, $modules, $locales, $url);
    }

    protected function taskDisableMaintenance()
    {
        return $this->task(DisableMaintenance::class);
    }

    protected function taskModuleEnable($initialModules)
    {
        return $this->task(ModuleEnable::class, $initialModules);
    }

    protected function taskEnablingDeveloperMode()
    {
        return $this->task(EnablingDeveloperMode::class);
    }

    protected function taskEnablingProductionMode()
    {
        return $this->task(EnablingProductionMode::class);
    }

    protected function taskInstallCode($path, $template, $package, $url = null)
    {
        return $this->task(InstallCode::class, $path, $template, $package, $url);
    }

    protected function taskModuleStatus()
    {
        return $this->task(ModuleStatus::class);
    }

    protected function taskReadCurrentLocales()
    {
        return $this->task(ReadCurrentLocales::class);
    }

    protected function taskReadExistingModules()
    {
        return $this->task(ReadExistingModules::class);
    }

    protected function taskSetupUpgrade()
    {
        return $this->task(SetupUpgrade::class);
    }

    protected function taskSetupDiCompile()
    {
        return $this->task(SetupDiCompile::class);
    }

    protected function taskStaticContentCompile($locales)
    {
        return $this->task(StaticContentCompile::class, $locales);
    }

    protected function taskCacheStatus()
    {
        return $this->task(CacheStatus::class);
    }

    protected function taskReadCacheStatus($cacheTypes)
    {
        return $this->task(ReadCacheStatus::class, $cacheTypes);
    }

    protected function taskCacheEnable($cachesToEnable)
    {
        return $this->task(CacheEnable::class, $cachesToEnable);
    }
}
