<?php

declare(strict_types=1);

namespace Danidoble\Firebird;

use Danidoble\Firebird\Query\Builder as FirebirdQueryBuilder;
use Danidoble\Firebird\Query\Grammars\FirebirdGrammar as FirebirdQueryGrammar;
use Danidoble\Firebird\Query\Processors\FirebirdProcessor as FirebirdQueryProcessor;
use Danidoble\Firebird\Schema\Builder as FirebirdSchemaBuilder;
use Danidoble\Firebird\Schema\Grammars\FirebirdGrammar as FirebirdSchemaGrammar;
use Illuminate\Database\Connection as DatabaseConnection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PDO;

class FirebirdConnection extends DatabaseConnection
{
    /**
     * Get the server version for the connection.
     */
    public function getServerVersion(): string
    {
        $version = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        return Str::match('/(?<=LI-V)\d+\.\d+\.\d+/', $version);
    }

    /**
     * Get the default query grammar instance.
     */
    protected function getDefaultQueryGrammar(): Grammar|FirebirdQueryGrammar
    {
        $grammar = new FirebirdQueryGrammar($this);
        $grammar = $grammar->setConnection($this);

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get the default post processor instance.
     */
    protected function getDefaultPostProcessor(): Processor|FirebirdQueryProcessor
    {
        return new FirebirdQueryProcessor;
    }

    /**
     * Get a schema builder instance for the connection.
     */
    public function getSchemaBuilder(): Builder|FirebirdSchemaBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new FirebirdSchemaBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     */
    protected function getDefaultSchemaGrammar(): null|\Illuminate\Database\Schema\Grammars\Grammar|FirebirdSchemaGrammar
    {
        $grammar = new FirebirdSchemaGrammar($this);
        $grammar = $grammar->setConnection($this);

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get a new query builder instance.
     */
    public function query(): FirebirdQueryBuilder|\Illuminate\Database\Query\Builder
    {
        return new FirebirdQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Execute a stored procedure.
     */
    public function executeProcedure(string $procedure, array $bindings = []): Collection
    {
        return $this->query()->procedure($procedure, $bindings)->get();
    }
}
