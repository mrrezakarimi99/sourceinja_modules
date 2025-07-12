<?php

namespace Sourceinja\RegisterModule\Console\Commands;

use Illuminate\Console\Command;
use Sourceinja\RegisterModule\RegisterModule;
use Sourceinja\RegisterModule\Services\ServiceFactory;

class SourceinjaListModule extends Command
{
    protected $signature = 'sourceinja:module-list
                            {--service= : The service to use (github/gitlab)}
                            {--detailed : Show detailed information including releases}';

    protected $description = 'List available modules from repository service';

    private RegisterModule $registerModule;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $service = ServiceFactory::make($this->option('service'));
            $this->registerModule = new RegisterModule($service);

            $this->registerModule->checkApiKey();

            $group = $this->registerModule->getAllGroups();
            $this->info("Using service: " . config('sourceinja.default_service'));
            $this->info("Found group: {$group['name']}");

            $subGroup = $this->registerModule->getSubGroups($group['id']);
            $this->info("Found modules group: {$subGroup['name']}");

            $detailed = $this->option('detailed');
            $projects = $this->registerModule->getProjects($subGroup['id'], $detailed);

            $this->displayProjects($projects, $detailed);

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function displayProjects($projects, $detailed)
    {
        $this->info("\nAvailable modules:");

        $headers = $detailed
            ? ['ID', 'Name', 'Default Branch', 'Last Updated', 'Latest Release']
            : ['ID', 'Name', 'Last Updated'];

        $rows = [];

        foreach ($projects as $project) {
            if ($detailed) {
                $latestRelease = 'No releases';
                if (!empty($project['release'])) {
                    $latestRelease = $project['release'][0]['name'] . ' (' . $project['release'][0]['tag_name'] . ')';
                }

                $rows[] = [
                    $project['id'],
                    $project['name'],
                    $project['default_branch'] ?? 'N/A',
                    $project['updated_at'],
                    $latestRelease
                ];
            } else {
                $rows[] = [
                    $project['id'],
                    $project['name'],
                    $project['updated_at']
                ];
            }
        }

        $this->table($headers, $rows);
    }
}