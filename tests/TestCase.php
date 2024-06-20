<?php

namespace Danidoble\Firebird\Tests;

use Danidoble\Firebird\Providers\FirebirdServiceProvider;
use Danidoble\Firebird\Tests\Support\MigrateDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use MigrateDatabase;

    protected function defineEnvironment($app)
    {
        Factory::guessFactoryNamesUsing(function ($class) {
            return 'Danidoble\\Firebird\\Tests\\Support\\Factories\\'.class_basename($class).'Factory';
        });

        if (env('TEST_ENV_FILE') && file_exists(__DIR__.'/'.env('TEST_ENV_FILE'))) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, env('TEST_ENV_FILE'));
            $dotenv->load();
        }

        $app['config']->set('database.default', 'firebird');
        $app['config']->set('database.connections.firebird', [
            'driver' => 'firebird',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3050'),
            'database' => env('DB_DATABASE', '/firebird/data/database.fdb'),
            'username' => env('DB_USERNAME', 'sysdba'),
            'password' => env('DB_PASSWORD', 'masterkey'),
            'charset' => env('DB_CHARSET', 'UTF8'),
        ]);
    }

    /**
     * Load package service provider.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            FirebirdServiceProvider::class,
        ];
    }

    /**
     * Determine the Firebird engine version of the current database connection.
     */
    public function getDatabaseEngineVersion(): float
    {
        return (float) DB::selectOne('SELECT rdb$get_context(\'SYSTEM\', \'ENGINE_VERSION\') as "version" from rdb$database')->version;
    }
}
