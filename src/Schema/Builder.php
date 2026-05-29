<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Schema;

use Closure;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class Builder extends SchemaBuilder
{
    public function create($table, Closure $callback): void
    {
        parent::create($table, $callback);

        if ($table === 'migrations') {
            $serverVersion = $this->connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);

            if (version_compare($serverVersion, '3.0', '<')) {
                $this->connection->statement('CREATE GENERATOR "GEN_MIGRATIONS_ID"');
                $this->connection->statement('SET GENERATOR "GEN_MIGRATIONS_ID" TO 0');
                $this->connection->statement('
                    CREATE TRIGGER "TRG_MIGRATIONS_ID" FOR "migrations"
                    ACTIVE BEFORE INSERT POSITION 0
                    AS
                    BEGIN
                        IF (NEW."id" IS NULL OR NEW."id" = 0) THEN
                            NEW."id" = NEXT VALUE FOR "GEN_MIGRATIONS_ID";
                    END
                ');
            }
        }
    }
}
