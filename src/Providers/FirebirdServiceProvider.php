<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Providers;

use Danidoble\Firebird\FirebirdConnection;
use Danidoble\Firebird\FirebirdConnector;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

final class FirebirdServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Connection::resolverFor('firebird', function ($connection, $database, $tablePrefix, $config) {
            return new FirebirdConnection($connection, $database, $tablePrefix, $config);
        });

        $this->app->bind('db.connector.firebird', FirebirdConnector::class);
    }
}
