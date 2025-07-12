<?php

namespace Sourceinja\RegisterModule\Traits;

trait InstallModule
{

    /**
     * @param $project
     *
     * @return void
     */
    public function cloneRepo($project, $branche, $urlPortocol): void
    {
        $url = $project[$urlPortocol] ?? $project['http_url_to_repo'];
        $name = $project['name'];

        [$tmpDestination, $moduleDirectory] = $this->getDirectories($name);

        $this->makeDirectory($tmpDestination);
        $this->progressBar->advance();

        $this->clone($url, $tmpDestination, $branche);
        $this->progressBar->advance();

        $this->makeDirectory($moduleDirectory);

        $this->move($tmpDestination, $moduleDirectory);
        $this->progressBar->advance();
    }

    /**
     * @param      $name
     * @param bool $temp
     *
     * @return bool
     */
    public function checkDirectory($name, bool $temp = false): bool
    {
        $directory = $temp ? storage_path('app/tmp') : base_path('modules');
        return file_exists($directory . '/' . $name);
    }

    /**
     * @param $name
     *
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
        return [$tmpDestination, $moduleDirectory];
    }

    /**
     * @param string $directory
     *
     * @return void
     */
    private function makeDirectory(string $directory): void
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        } else {
            $this->newLine();
            $this->error("The directory $directory already exists");
            exit();
        }
    }

    /**
     * @param        $url
     * @param string $directory
     *
     * @return void
     */
    private function clone($url, string $directory, string $branche): void
    {
        if (str_contains($url, 'git@')) {
            $this->cloneSSH($url, $directory, $branche);
        } elseif (str_contains($url, 'https://')) {
            $this->cloneHTTPS($url, $directory, $branche);
        } else {
            $this->newLine();
            $this->error('The url is not valid');
            exit();
        }
    }

    /**
     * @param string $url
     * @param string $directory
     * @param string $branche
     *
     * @return void
     */
    private function cloneHTTPS(string $url, string $directory, string $branche): void
    {
        $portocol = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $token = config('sourceinja.gitlab_api_key');
        $command = "git clone --branch $branche https://oauth2:$token@$host$path $directory";
        $this->runCommandWithLog($command);
    }

    /**
     * @param string $url
     * @param string $directory
     * @param string $branche
     *
     * @return void
     */
    private function cloneSSH(string $url, string $directory, string $branche): void
    {
        $this->runCommandWithLog("git clone --branch $branche $url $directory");
    }


    /**
     * @param string $tmpDestination
     * @param string $moduleDirectory
     *
     * @return void
     */
    private function move(string $tmpDestination, string $moduleDirectory): void
    {
        $this->runCommandWithLog("mv $tmpDestination/* $moduleDirectory");
        $this->runCommandWithLog("mv $tmpDestination/.git $moduleDirectory");
        $this->runCommandWithLog("rm -rf $tmpDestination");
    }

    private function runCommandWithLog(string $command): void
    {
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $log = "[" . now() . "] CMD: $command\n";
        $log .= "Return code: $returnCode\n";
        $log .= implode("\n", $output) . "\n\n";

        $this->log($log);

        if ($returnCode !== 0) {
            $this->newLine();
            $this->error("Command failed with return code $returnCode");
            exit();
        }
    }

    private function log(string $log): void
    {
        file_put_contents(storage_path('logs/git.log'), $log . PHP_EOL, FILE_APPEND);
    }
}
