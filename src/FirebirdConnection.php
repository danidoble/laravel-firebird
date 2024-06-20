<?php

declare(strict_types=1);

namespace Danidoble\Firebird;

use Danidoble\Firebird\Query\Builder as FirebirdQueryBuilder;
use Danidoble\Firebird\Query\Grammars\FirebirdGrammar as FirebirdQueryGrammar;
use Danidoble\Firebird\Query\Processors\FirebirdProcessor as FirebirdQueryProcessor;
use Danidoble\Firebird\Schema\Builder as FirebirdSchemaBuilder;
use Danidoble\Firebird\Schema\Grammars\FirebirdGrammar as FirebirdSchemaGrammar;
use Illuminate\Database\Connection as DatabaseConnection;
use Illuminate\Database\Grammar;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;

use function method_exists;

class FirebirdConnection extends DatabaseConnection
{
    /**
     * Get the default query grammar instance.
     */
    protected function getDefaultQueryGrammar(): FirebirdQueryGrammar
    {
        $grammar = new FirebirdQueryGrammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $grammar;
    }

    /**
     * Get the default post processor instance.
     */
    protected function getDefaultPostProcessor(): FirebirdQueryProcessor
    {
        return new FirebirdQueryProcessor;
    }

    /**
     * Get a schema builder instance for this connection.
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
    protected function getDefaultSchemaGrammar(): Grammar
    {
        $grammar = new FirebirdSchemaGrammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get a new query builder instance.
     */
    public function query(): FirebirdQueryBuilder
    {
        return new FirebirdQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Execute a stored procedure.
     */
    public function executeProcedure(string $procedure, array $values = []): Collection
    {
        return $this->query()->fromProcedure($procedure, $values)->get();
    }
}
