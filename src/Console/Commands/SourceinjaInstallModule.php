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
        $this->startCommand();

        $projects = $this->getProjects();
        $project = $this->getModuleFromUser($projects);

        $this->cloneRepo($project);

        $this->registerModuleToCore($project);

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
        $progressBar = new ProgressBar($this->output , $totalItems);
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

        $projects = $this->registerModule->getProjects($sub['id'] , true);
        $this->progressBar->advance();
        return $projects;
    }

    /**
     * @param $projects
     * @return array
     */
    public function getModuleFromUser($projects): array
    {
        $options = $projects->map(function ($project) {
            return $project['name'];
        })->toArray();
        $this->progressBar->advance();

        $project = $this->choice('Please select project to install' , $options);
        $this->progressBar->advance();

        $project = $projects->where('name' , $project)->first();
        if ($this->checkDirectory($project['name'])) {
            $this->newLine();
            $this->error('Module already installed');
            exit();
        }
        $this->progressBar->advance();
        return $project;
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
     * @return void
     */
    private function registerModuleToCore($project): void
    {
        $basePathModule = base_path('Modules');
        $dependencies = $this->getDependencies($basePathModule , [
            'Core' , 'Example' , $project['name']
        ]);
        $this->progressBar->advance();

        $this->registerConfig($basePathModule , $project['name'] , $dependencies);
    }

    /**
     * @param $basePathModule
     * @param $ignore
     * @return array
     */
    public function getDependencies($basePathModule , $ignore): array
    {
        $listOfModules = scandir($basePathModule);
        $listOfModules = array_filter($listOfModules , function ($item) {
            return !in_array($item , ['.' , '..']);
        });
        $listOfModules = array_diff($listOfModules , $ignore);
        if (count($listOfModules) >= 1) {
            return $this->choice('Please select dependencies (separate with comma)' , $listOfModules , null , null , true);
        } else {
            return [];
        }
    }

    /**
     * @param $basePathModule
     * @param $name
     * @param array $dependencies
     * @return void
     */
    private function registerConfig($basePathModule , $name , array $dependencies): void
    {
        $coreDir = scandir($basePathModule . '/Core/Config');
        foreach ($coreDir as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!str_contains($file , 'modules')) {
                continue;
            }
            $lowerName = strtolower($name);
            $content = file_get_contents($basePathModule . '/Core/Config/' . $file);
            $lastIndex = strrpos($content , ']');
            $content = substr($content , 0 , $lastIndex);
            $content .= "    '" . $name . "' => [\n";
            $content .= "        'name'        => '" . $name . "' ,\n";
            $content .= "        'description' => '" . $name . " Module' ,\n";
            $content .= "        'status'      => true ,\n";
            $content .= "        'services'    => [\n";
            $content .= "            'provider' => 'Modules\\\\$name\\\\" . $name . "ServiceProvider' ,\n";
            $content .= "            'lang' => [\n";
            $content .= "                'path' => 'Modules/$name/lang' ,\n";
            $content .= "                'name' => '$lowerName' ,\n";
            $content .= "            ] ,\n";
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
            file_put_contents($basePathModule . '/Core/Config/' . $file , $content);
            $this->progressBar->advance();
        }
    }
}
