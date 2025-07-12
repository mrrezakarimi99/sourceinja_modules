<?php

namespace Sourceinja\RegisterModule;

use Illuminate\Support\ServiceProvider;
use Sourceinja\RegisterModule\Console\Commands\SourceinjaInstallModule;
use Sourceinja\RegisterModule\Console\Commands\SourceinjaListModules;

class RegisterModuleProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/Config/sourceinja.php' => config_path('sourceinja.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SourceinjaInstallModule::class,
                SourceinjaListModules::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/sourceinja.php', 'sourceinja');
    }
}