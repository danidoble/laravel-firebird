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
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        return $this->createConnection($dsn, $config, $options);
    }

    /**
     * Create a DSN string from the configuration.
     */
    protected function getDsn(array $config): string
    {
        if (! isset($config['host']) || ! isset($config['database'])) {
            trigger_error('Cannot connect to Firebird Database, no host or database supplied');

            return '';
        }

        $dsn = "firebird:dbname={$config['host']}";

        if (isset($config['port'])) {
            $dsn .= "/{$config['port']}";
        }

        $dsn .= ":{$config['database']};";

        if (isset($config['role'])) {
            $dsn .= "role={$config['role']};";
        }

        if (isset($config['charset'])) {
            $dsn .= "charset={$config['charset']};";
        }

        return $dsn;
    }
}
