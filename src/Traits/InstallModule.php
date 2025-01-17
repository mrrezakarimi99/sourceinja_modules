<?php

namespace Sourceinja\RegisterModule\Traits;

use Sourceinja\RegisterModule\Exceptions\SourceinjaException;

trait InstallModule
{

    /**
     * @param $project
     * @return void
     */
    public function cloneRepo($project): void
    {
        $url = $project['http_url_to_repo'];
        $name = $project['name'];

        list($tmpDestination , $moduleDirectory) = $this->getDirectories($name);

        $this->makeDirectory($tmpDestination);
        $this->progressBar->advance();

        $this->clone($url , $tmpDestination);
        $this->progressBar->advance();

        $this->makeDirectory($moduleDirectory);

        $this->move($tmpDestination , $moduleDirectory);
        $this->progressBar->advance();
    }

    /**
     * @param $name
     * @param bool $temp
     * @return bool
     */
    public function checkDirectory($name , bool $temp = false): bool
    {
        $directory = $temp ? storage_path('app/tmp') : base_path('modules');
        return file_exists($directory . '/' . $name);
    }

    /**
     * @param $name
     * @return string[]
     */
    private function getDirectories($name): array
    {
        $tmpDirectory = storage_path('app/tmp');
        $this->progressBar->advance();

        $tmpDestination = $tmpDirectory . '/' . $name;
        $this->progressBar->advance();

        $moduleDirectory = base_path('modules') . '/' . $name;
        $this->progressBar->advance();
        return [$tmpDestination , $moduleDirectory];
    }

    /**
     * @param string $directory
     * @return void
     */
    private function makeDirectory(string $directory): void
    {
        if (!file_exists($directory)) {
            mkdir($directory , 0777 , true);
        } else {
            $this->newLine();
            $this->error("The directory $directory already exists");
            exit();
        }
    }

    /**
     * @param $url
     * @param string $directory
     * @return void
     */
    private function clone($url , string $directory): void
    {
        $portocol = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $token = config('sourceinja.gitlab_api_key'); 
        exec("git clone $portocol://oauth2:$token@$host$path $directory > storage/logs/git.log");
    }

    /**
     * @param string $tmpDestination
     * @param string $moduleDirectory
     * @return void
     */
    private function move(string $tmpDestination , string $moduleDirectory): void
    {
        exec("mv $tmpDestination/* $moduleDirectory > storage/logs/git.log 2>&1");
        exec("mv $tmpDestination/.git $moduleDirectory > storage/logs/git.log 2>&1");
        exec("rm -rf $tmpDestination > storage/logs/git.log 2>&1");
    }
}
