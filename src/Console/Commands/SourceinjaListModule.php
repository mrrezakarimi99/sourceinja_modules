<?php

namespace Sourceinja\RegisterModule\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Sourceinja\RegisterModule\RegisterModule;

class SourceinjaListModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sourceinja:module-list';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all modules';

    /**
     * @throws Exception
     */
    public function handle()
    {
        $registered = new RegisterModule();
        $registered->checkApiKey();
        $group = $registered->getAllGroups();
        $sub = $registered->getSubGroups($group['id']);
        $projects = $registered->getProjects($sub['id']);
        $this->table(['ID' , 'Name' , 'updated_at'] , $projects);
    }
}
