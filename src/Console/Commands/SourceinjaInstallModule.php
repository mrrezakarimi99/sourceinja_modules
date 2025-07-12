<?php

namespace Sourceinja\RegisterModule\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Sourceinja\RegisterModule\RegisterModule;
use Sourceinja\RegisterModule\Traits\InstallModule;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SourceinjaInstallModule extends Command
{
    use InstallModule;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sourceinja:install-module';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install module from gitlab';

    /**
     * @var RegisterModule
     */
    private RegisterModule $registerModule;

    /**
     * @var ProgressBar
     */
    public ProgressBar $progressBar;

    public function __construct()
    {
        parent::__construct();
        $this->registerModule = new RegisterModule();
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->registerModule->checkApiKey();
        $this->startCommand();

        $projects = $this->getProjects();
        $project = $this->getModuleFromUser($projects);

        $branches = $this->registerModule->getBranches($project);
        $branche = $this->getBranchFromUser($branches);

        $urlPortocol = $this->getPortocolFromUser();
        $this->cloneRepo($project, $branche, $urlPortocol);

        $this->registerModuleToCore($project, $branche, $urlPortocol);

        $this->finishCommand();
    }

    /**
     * @return void
     */
    private function startCommand(): void
    {
        $this->info('Welcome to Sourceinja');
        $this->info('wait for loading projects');
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $totalItems = 14;
        $progressBar = new ProgressBar($this->output, $totalItems);
        $this->progressBar = $progressBar;
        $this->progressBar->start();
    }

    /**
     * @return Collection
     */
    public function getProjects(): Collection
    {
        $group = $this->registerModule->getAllGroups();
        $this->progressBar->advance();

        $sub = $this->registerModule->getSubGroups($group['id']);
        $this->progressBar->advance();

        $projects = $this->registerModule->getProjects($sub['id'], true);
        $this->progressBar->advance();
        return $projects;
    }

    /**
     * @param $projects
     *
     * @return array
     */
    public function getModuleFromUser($projects): array
    {
        $options = $projects->map(function ($project) {
            return $project['name'];
        })->toArray();
        $this->progressBar->advance();

        $project = $this->choice('Please select project to install', $options);
        $this->progressBar->advance();

        $project = $projects->where('name', $project)->first();
        $project['ssh_url_to_repo'] = $this->convertToSSHUrl($project['http_url_to_repo']);
        if ($this->checkDirectory($project['name'])) {
            $this->newLine();
            $this->error('Module already installed');
            exit();
        }
        $this->progressBar->advance();
        return $project;
    }

    public function getBranchFromUser($branches): string
    {
        $options = collect($branches)->map(function ($branch) {
            return $branch['name'];
        })->toArray();
        $this->progressBar->advance();

        $branch = $this->choice('Please select branch to install', $options);
        $this->progressBar->advance();

        return $branch;
    }

    /**
     * @return void
     */
    public function finishCommand(): void
    {
        $this->progressBar->finish();
        $this->newLine();
        $this->info('Module installed successfully');
    }

    /**
     * @param $project
     *
     * @return void
     */
    private function registerModuleToCore($project, $branch, $urlPortocol): void
    {
        $basePathModule = base_path('modules');
        $dependencies = $this->getDependencies($basePathModule, [
            'Core', 'Example', $project['name'],
        ]);
        $this->progressBar->advance();
        $this->registerConfig($basePathModule, $project['name'], $dependencies);
        $this->registerModule($project, $branch, $urlPortocol);
    }

    /**
     * @param $basePathModule
     * @param $ignore
     *
     * @return array
     */
    public function getDependencies($basePathModule, $ignore): array
    {
        $listOfModules = scandir($basePathModule);
        $listOfModules = array_filter($listOfModules, function ($item) {
            return !in_array($item, ['.', '..']);
        });
        $listOfModules = array_diff($listOfModules, $ignore);
        if (count($listOfModules) >= 1) {
            return $this->choice('Please select dependencies (separate with comma)', $listOfModules, null, null, true);
        } else {
            return [];
        }
    }

    /**
     * @param       $basePathModule
     * @param       $name
     * @param array $dependencies
     *
     * @return void
     */
    private function registerConfig($basePathModule, $name, array $dependencies): void
    {
        $coreDir = scandir($basePathModule . '/Core/Config');
        foreach ($coreDir as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!str_contains($file, 'modules')) {
                continue;
            }
            $lowerName = strtolower($name);
            $content = file_get_contents($basePathModule . '/Core/Config/' . $file);
            $lastIndex = strrpos($content, ']');
            $content = substr($content, 0, $lastIndex);
            $content .= "    '" . $name . "' => [\n";
            $content .= "        'name'        => '" . $name . "' ,\n";
            $content .= "        'description' => '" . $name . " Module' ,\n";
            $content .= "        'status'      => true ,\n";
            $content .= "        'services'    => [\n";
            $content .= "            'provider' => 'Modules\\\\$name\\\\Providers\\$name" . "ServiceProvider' ,\n";
            $content .= "            'lang' => [\n";
            $content .= "                'path' => 'Modules/$name/lang' ,\n";
            $content .= "                'name' => '$lowerName' ,\n";
            $content .= "            ] ,\n";
            $content .= "        ] ,\n";
            $content .= "        'view'         => [\n";
            $content .= "            'path' => 'Modules/$name/Resources/views' ,\n";
            $content .= "        ] ,\n";
            if (count($dependencies) > 0) {
                $content .= "        'dependencies' => [\n";
                foreach ($dependencies as $dependency) {
                    $content .= "            '$dependency' ,\n";
                }
                $content .= "        ] ,\n";
            } else {
                $content .= "        'dependencies' => [] ,\n";
            }
            $content .= "    ] ,\n";
            $content .= "];\n";
            file_put_contents($basePathModule . '/Core/Config/' . $file, $content);
            $this->progressBar->advance();
        }
    }

    private function registerModule(array $project, string $branch, string $urlPortocol): void
    {
        $url = $project['ssh_url_to_repo'];
        $name = $project['name'];

        $gitModules = file_exists(base_path('.gitmodules')) ? file_get_contents(base_path('.gitmodules')) : '';
        $gitModules .= "[submodule \"modules/$name\"]\n";
        $gitModules .= "    path = modules/$name\n";
        $gitModules .= "    url = $url\n";
        $gitModules .= "    branch = $branch\n";
        file_put_contents(base_path('.gitmodules'), $gitModules);

        chdir(base_path());

        $cmd = 'git submodule add -f ' . $url . ' ./modules/' . $name;
        exec($cmd, $output);
        exec('git submodule sync');
        file_put_contents(storage_path('logs/git.log'), implode("\n", $output), FILE_APPEND);
    }

    private function convertToSSHUrl(string $url): string
    {
        if (str_starts_with($url, 'https://')) {
            $url = str_replace('https://', 'git@', $url);
            $firstSlashPos = strpos($url, '/');
            if ($firstSlashPos !== false) {
                $url = substr_replace($url, ':', $firstSlashPos, 1);
            }
        }
        return $url;
    }

    public function getPortocolFromUser()
    {
        $options = ['SSH', 'HTTP'];
        $this->progressBar->advance();

        $protocol = $this->choice('Please select protocol to use', $options);
        $this->progressBar->advance();

        if ($protocol === 'SSH') {
            return 'ssh_url_to_repo';
        } else {
            return 'http_url_to_repo';
        }

    }
}
