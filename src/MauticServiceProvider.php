<?php

namespace Triibo\Mautic;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class MauticServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() : void
    {
        // Publish Configuration File to base Path.
        $this->publishes( [
            __DIR__."/config/mautic.php" => base_path( "config/mautic.php" ),
            __DIR__."/migrations" => $this->app->databasePath()."/migrations",
        ] );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() : void
    {
        $this->registerFactory( $this->app );
        $this->registerManager( $this->app );
        $this->registerRoutes( $this->app );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            "mautic",
            "mautic.factory",
        ];
    }

    /**
     * Register the factory class.
     *
     * @param Application $app
     *
     * @return void
     */
    protected function registerFactory( Application $app ) : void
    {
        $app->singleton( "mautic.factory", static function ()
        {
            return new Factories\MauticFactory();
        } );

        $app->alias( "mautic.factory", "Triibo\Mautic\Factories\MauticFactory" );
    }

    /**
     * Register the manager class.
     *
     * @param Application $app
     *
     * @return void
     */
    protected function registerManager( Application $app ) : void
    {
        $app->singleton( "mautic", static function ( $app )
        {
            $config = $app[ "config" ];
            $factory = $app[ "mautic.factory" ];

            return new Mautic( $config, $factory );
        } );

        $app->alias( "mautic", "Triibo\Mautic\Mautic" );
    }

    /**
     * Get the routes services provided by the provider.
     *
     * @param Application $app
     *
     * @return void
     */
    protected function registerRoutes( Application $app ) : void
    {
        $app[ "router" ]->group( [
            "namespace" => "Triibo\Mautic\Http\Controllers",
            "prefix" => "mautic",
        ], function () : void
        {
            require __DIR__."/Http/routes.php";
        }
        );
    }
}
