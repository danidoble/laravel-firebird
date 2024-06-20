<?php

declare(strict_types=1);

namespace Danidoble\Firebird;

use Exception;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use PDO;

class FirebirdConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @throws Exception
     */
    public function connect(array $config): PDO
    {
        return $this->createConnection(
            $this->getDsn($config),
            $config,
            $this->getOptions($config)
        );
    }

    /**
     * Create a DSN string from the configuration.
     */
    protected function getDsn(array $config): string
    {
        extract($config);

        if (! isset($host) || ! isset($database)) {
            trigger_error('Cannot connect to Firebird Database, no host or database supplied');
            return '';
        }

        $dsn = "firebird:dbname=$host";

        if (isset($port)) {
            $dsn .= "/$port";
        }

        $dsn .= ":$database;";

        if (isset($role)) {
            $dsn .= "role=$role;";
        }

        if (isset($charset)) {
            $dsn .= "charset=$charset;";
        }

        return $dsn;
    }
}
