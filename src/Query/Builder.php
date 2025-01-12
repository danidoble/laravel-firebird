<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Query;

use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * Determine if any rows exist for the current query.
     */
    public function exists(): bool
    {
        return parent::count() > 0;
    }

    /**
     * Set the stored procedure which the query is targeting.
     *
     * @return QueryBuilder|Builder
     */
    public function procedure(string $procedure, array $bindings = []): QueryBuilder|static
    {
        $expression = $this->grammar->compileProcedure($this, $procedure, $bindings);

        $this->fromRaw($expression, $this->cleanBindings($bindings));

        return $this;
    }

    /**
     * Alias to set the stored procedure which the query is targeting.
     *
     * @return Builder|QueryBuilder
     *
     * @deprecated This method is deprecated and will be removed in a future
     * release. Use the `procedure` method instead.
     */
    public function fromProcedure(string $procedure, array $bindings = []): Builder|QueryBuilder|static
    {
        return $this->procedure($procedure, $bindings);
    }
}
