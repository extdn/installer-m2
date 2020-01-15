<?php

namespace ExtDN\Task;

use Robo\Result;

class CheckPermissions extends \Robo\Task\BaseTask
{

    /**
     * @return \Robo\Result
     */
    public function run()
    {
        try {
            $this->checkCurrentUserVsExistingFiles();
            $this->checkForSufficientPermissions();
            return Result::success($this);
        } catch (\Exception $e) {
            $this->printTaskWarning($this->getFileOwnerShipSuggestions());
            //TODO display the fileOwnerShip Suggestions after the exception.
            return Result::fromException($this, $e);
        }
    }

    private function checkCurrentUserVsExistingFiles()
    {
        $this->printTaskInfo('Checking current user.');
        if (!function_exists('posix_getpwuid')) {
            throw new \RuntimeException('Function posix_getpwuid() not available.');
        }
        $currentProcessUserId = posix_geteuid();
        if (fileowner(getcwd() . '/composer.json') !== $currentProcessUserId) {
            throw new \RuntimeException(
                sprintf(
                    'Current user: %s [%s] is different to file owner of composer.json: %s [%s]',
                    posix_getpwuid($currentProcessUserId)['name'],
                    $currentProcessUserId,
                    posix_getpwuid(fileowner(getcwd() . '/composer.json'))['name'],
                    fileowner(getcwd() . '/composer.json')
                )
            );
        }

        if (fileowner(getcwd() . '/vendor') !== $currentProcessUserId) {
            throw new \RuntimeException(
                sprintf(
                    'Current user: %s [%s] is different to file owner of /vendor: %s [%s]',
                    posix_getpwuid($currentProcessUserId)['name'],
                    $currentProcessUserId,
                    posix_getpwuid(fileowner(getcwd() . '/vendor'))['name'],
                    fileowner(getcwd() . '/vendor')
                )
            );
        }
        $this->printTaskSuccess('[OK]');
    }

    private function checkForSufficientPermissions()
    {
        $this->printTaskInfo('Checking file ownership.');
        $output = [];
        exec('find . ! -writable -not -path "*.git/objects/pack*"', $output, $return);

        if ($return !== 0) {
            return Result::error(
                $this,
                'Not all files are writable for the current user'.
                $this->getFileOwnerShipSuggestions()
            );
        }

        if (!empty($output)) {
            return Result::error(
                $this,
                'The following files are not writable for the current user' . PHP_EOL . implode(PHP_EOL, $output).
                $this->getFileOwnerShipSuggestions()
            );
        }
        $this->printTaskSuccess('[OK]');
    }

    private function getFileOwnerShipSuggestions()
    {
        //TODO we can probably make this more specific to the actual encountered issue
        $currentProcessUserId = posix_geteuid();
        return PHP_EOL . PHP_EOL . implode(
                PHP_EOL,
            [
                'Suggestion on how to fix:',
                '',
                'Change your current user to the file owner:',
                sprintf('su - %s -s /bin/sh', posix_getpwuid($currentProcessUserId)['name']),
                '',
                'OR change the file ownership to the current user:',
                sprintf('sudo chown %s -R %s', posix_getpwuid($currentProcessUserId)['name'], getcwd()),
                '',
                'OR change the file permissions for the current user:',
                sprintf('chmod +w -R %s', getcwd()),
                '',
                'If in doubt please check with your server administrator or webhosting provider before executing any commands.'.
                ''
            ]
        );
    }
}
