<?php

namespace Sourceinja\RegisterModule\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Sourceinja\RegisterModule\RegisterModule;
use Sourceinja\RegisterModule\Services\ServiceFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SourceinjaInstallModule extends Command
{
    protected $signature = 'sourceinja:install {--service= : The service to use (github/gitlab)}';

    protected $description = 'Install module from repository service';

    private RegisterModule $registerModule;

    public ProgressBar $progressBar;

    public function __construct()
    {
        parent::__construct();
        $service = ServiceFactory::make($this->option('service'));
        $this->registerModule = new RegisterModule($service);
    }

    // Rest of the class remains the same...

    private function cloneRepo($project): void
    {
        $this->registerModule->cloneRepo($project);
        $this->progressBar->advance();
    }
}