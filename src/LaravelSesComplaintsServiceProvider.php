<?php

namespace Oza75\LaravelSesComplaints;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Oza75\LaravelSesComplaints\Commands\SubscribeUrlCommand;
use Oza75\LaravelSesComplaints\Contracts\LaravelSesComplaints as Contract;
use Oza75\LaravelSesComplaints\Listeners\CheckIsMessageShouldBeSend;
// 2023-11-13; This line isn't working. Needed for testing, but we can figure out a solution later.
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class LaravelSesComplaintsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // 2023-11-13; This line isn't working. Needed for testing, but we can figure out a solution later.
        //app()->make(EloquentFactory::class)->load(__DIR__.'/database/factories');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('laravel-ses-complaints.php'),
            ], 'config');

            // Registering package commands.
            $this->commands([
                SubscribeUrlCommand::class,
            ]);

            $this->publishMigrations(['create_sns_subscriptions_table.php', 'create_ses_notifications_table.php']);
        }

        Event::listen(MessageSending::class, CheckIsMessageShouldBeSend::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'laravel-ses-complaints');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-ses-complaints', function () {
            return new LaravelSesComplaints;
        });

        $this->app->singleton(Contract::class, LaravelSesComplaints::class);
    }

    /**
     * @param array $paths
     */
    protected function publishMigrations(array $paths)
    {
        $paths = array_filter($paths, function ($path) {
            return empty(glob(database_path("/migrations/*_$path")));
        });

        $toPublish = [];

        foreach ($paths as $path) {
            $toPublish[__DIR__ . '/../database/migrations/' . $path] = database_path('migrations/' . date('Y_m_d_His', time()) . '_' . $path);
        }

        $this->publishes($toPublish, 'migrations');
    }
}
