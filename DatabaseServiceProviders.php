<?php namespace Nano7\Database;

use Nano7\Database\Connection;
use Nano7\Database\Migrations\Migrator;
use Nano7\Foundation\Support\ServiceProvider;

class DatabaseServiceProviders extends ServiceProvider
{
    /**
     * Register objetos base.
     */
    public function register()
    {
        $this->registerManager();

        $this->registerMigrator();
    }

    /**
     * Register manager.
     */
    protected function registerManager()
    {
        $this->app->singleton('db', function () {
            $manager = new DataManager($this->app, $this->app['config']->get('database', []));

            // Driver Mongo
            $this->registerMongoDb($manager);

            return $manager;
        });
    }

    /**
     * Register driver mongodb.
     *
     * @param DataManager $manager
     */
    protected function registerMongoDb(DataManager $manager)
    {
        $manager->extend('mongodb', function($app, $config) {
            return new Connection($config);
        });
    }

    /**
     * Register migrator
     */
    protected function registerMigrator()
    {
        $this->app->singleton('migrator', function () {
            return new Migrator($this->app['files']);
        });

        // Comandos da migração base
        $this->command('\Nano7\Database\Console\MigrateCommand');
    }
}