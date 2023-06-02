<?php

namespace Sourceinja\RegisterModule;

use Illuminate\Support\ServiceProvider;
use Sourceinja\RegisterModule\Console\Commands\SourceinjaInstallModule;
use Sourceinja\RegisterModule\Console\Commands\SourceinjaListModule;

class RegisterModuleProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/sourceinja.php' , 'sourceinja');
        $this->commands([
            SourceinjaListModule::class ,
            SourceinjaInstallModule::class
        ]);


    }
}
