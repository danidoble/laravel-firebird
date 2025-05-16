<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Str;
use RuntimeException;

class FirebirdGrammar extends Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'offset',
        'limit',
        'lock',
    ];

    /**
     * All the available clause operators.
     *
     * @var array
     *
     * @link https://ib-aid.com/download/docs/firebird-language-reference-2.5/fblangref25-commons-predicates.html
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        '!<', '!>', '~<', '~>', '^<', '^>', '~=', '^=',
        'like', 'not like', 'between', 'not between',
        'containing', 'not containing', 'starting with', 'not starting with',
        'similar to', 'not similar to', 'is distinct from', 'is not distinct from',
    ];

    /**
     * Compile the "select *" portion of the query.
     *
     * @param  array  $columns
     */
    protected function compileColumns(Builder $query, $columns): ?string
    {
        // See superclass.
        if (! is_null($query->aggregate)) {
            return null;
        }

        $select = 'select ';

        // Before Firebird v3, the syntax used to limit and offset rows is
        // "select first [int] skip [int] * from table". Laravel's query builder
        // doesn't natively support inserting components between "select" and
        // the column names, so compile the limit and offset here.

        if (isset($query->limit) && $usesLegacyLimitAndOffset ??= $this->usesLegacyLimitAndOffset()) {
            $select .= $this->compileLegacyLimit($query, $query->limit).' ';
        }

        if (isset($query->offset) && $usesLegacyLimitAndOffset ??= $this->usesLegacyLimitAndOffset()) {
            $select .= $this->compileLegacyOffset($query, $query->offset).' ';
        }

        if ($query->distinct) {
            if (is_array($query->distinct)) {
                throw new RuntimeException('This database engine does not support distinct on specific columns.');
            }

            $select .= 'distinct ';
        }

        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param  int  $limit
     */
    protected function compileLimit(Builder $query, $limit): string
    {
        if ($this->usesLegacyLimitAndOffset()) {
            return '';
        }

        return 'fetch first '.(int) $limit.' rows only';
    }

    /**
     * Compile the "limit" portions of the query for legacy versions of Firebird.
     */
    protected function compileLegacyLimit(Builder $query, int|string $limit): string
    {
        return 'first '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  int  $offset
     */
    protected function compileOffset(Builder $query, $offset): string
    {
        if ($this->usesLegacyLimitAndOffset()) {
            return '';
        }

        return 'offset '.(int) $offset.' rows';
    }

    /**
     * Compile the "offset" portions of the query for legacy versions of Firebird.
     */
    protected function compileLegacyOffset(Builder $query, int|string $offset): string
    {
        return 'skip '.(int) $offset;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     */
    public function compileRandom($seed): string
    {
        return 'rand()';
    }

    /**
     * Wrap a union subquery in parentheses.
     *
     * @param  string  $sql
     */
    protected function wrapUnion($sql): string
    {
        return 'select * from ('.$sql.')';
    }

    /**
     * Compile the "union" queries attached to the main query.
     */
    protected function compileUnions(Builder $query): string
    {
        $sql = '';

        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (! empty($query->unionOrders)) {
            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
        }

        // Swap the default order of limit and offset for union queries.

        if (isset($query->unionOffset)) {
            if ($usesLegacyLimitAndOffset ??= $this->usesLegacyLimitAndOffset()) {
                throw new RuntimeException('This database engine does not support offset on union queries.');
            }

            $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
        }

        if (isset($query->unionLimit)) {
            if ($usesLegacyLimitAndOffset ?? $this->usesLegacyLimitAndOffset()) {
                throw new RuntimeException('This database engine does not support limit on union queries.');
            }

            $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
        }

        return ltrim($sql);
    }

    /**
     * Compile a date based where clause.
     *
     * @param  string  $type
     * @param  array  $where
     */
    protected function dateBasedWhere($type, Builder $query, $where): string
    {
        $condition = ($type === 'date' || $type === 'time')
            ? 'cast('.$this->wrap($where['column']).' as '.$type.') '
            : 'extract('.$type.' from '.$this->wrap($where['column']).') ';

        $condition .= $where['operator'].' '.$this->parameter($where['value']);

        return $condition;
    }

    /**
     * Compile the select clause for a stored procedure.
     */
    public function compileProcedure(Builder $query, string $procedure, array $values = []): string
    {
        $procedure = $this->wrap($procedure);

        return $procedure.' ('.$this->parameterize($values).')';
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  array  $aggregate
     */
    protected function compileAggregate(Builder $query, $aggregate): string
    {
        // Wrap `aggregate` in double quotes to ensure the result set returns the
        // column name as a lowercase string. This resolves compatibility with
        // the framework's paginator.
        return Str::replaceLast(
            'as aggregate', 'as "aggregate"', parent::compileAggregate($query, $aggregate)
        );
    }

    /**
     * Compile a "lateral join" clause.
     */
    public function compileJoinLateral($join, string $expression): string
    {
        return trim("{$join->type} join lateral {$expression} on true");
    }

    /**
     * Determine if the database uses the legacy limit and offset syntax.
     */
    protected function usesLegacyLimitAndOffset(): bool
    {
        if(config('database.firebird.legacy_limit_and_offset', true)) {
            return config('database.firebird.legacy_limit_and_offset', true);
        }
        return version_compare($this->connection->getServerVersion(), '3.0.0', '<');
    }

    public function whereDate(Builder $query, $where): string
    {
        return $this->dateBasedWhere('date', $query, $where);
    }

    public function whereTime(Builder $query, $where): string
    {
        return $this->dateBasedWhere('time', $query, $where);
    }
}
