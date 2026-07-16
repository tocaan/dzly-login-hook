<?php
namespace  DzlyLoginHook\Providers;

use Illuminate\Support\ServiceProvider;

class DzlyLoginHookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/dzly-login-hook.php' => config_path('dzly-login-hook.php'),
        ], 'config');

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dzly-login-hook');

        // Routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Register blade component
        // Blade::component('dzly-hook-login', Login::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/dzly-login-hook.php', 'dzly-login-hook'
        );
    }
}
