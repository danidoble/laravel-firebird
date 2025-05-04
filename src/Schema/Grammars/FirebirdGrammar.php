<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Schema\Grammars;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Fluent;
use LogicException;

class FirebirdGrammar extends Grammar
{
    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = ['Charset', 'Collate', 'Increment', 'Nullable', 'Default'];

    /**
     * The columns available as serials.
     */
    protected array $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * Compile the query to determine the tables.
     */
    public function compileTables($schema = null): string
    {
        return 'select trim(trailing from rdb$relation_name) as "name" '
            .'from rdb$relations '
            .'where rdb$relation_type = 0 '
            .'and (rdb$system_flag is null or rdb$system_flag = 0) '
            .'order by rdb$relation_name';
    }

    /**
     * Compile the query to determine if a table exists.
     */
    public function compileTableExists($schema, $table): string
    {
        if(App::version() < 12 && is_null($table)){
            $table = $schema;
        }
        $table = str_replace('"', "'", $this->wrapTable($table));

        return 'select rdb$relation_name from rdb$relations where rdb$relation_name = '.$table;
    }

    /**
     * Compile the query to determine the views.
     */
    public function compileViews($schema = null): string
    {
        return 'select trim(trailing from rdb$relation_name) as "name", '
            .'rdb$view_source as "definition" '
            .'from rdb$relations '
            .'where rdb$view_blr is not null '
            .'and (rdb$system_flag is null or rdb$system_flag = 0)';
    }

    /**
     * Compile the query to determine the columns.
     */
    public function compileColumns($schema = null, $table = null): string
    {
        if(App::version() < 12 && is_null($table)){
            $table = $schema;
        }
        return 'select trim(trailing from rdb$field_name) as "name" '
            .'from rdb$relation_fields '
            .'where rdb$relation_name = '.$this->quoteString($table).' '
            .'order by rdb$relation_name';
    }

    /**
     * Compile the query to determine the list of columns.
     */
    public function compileColumnListing(string $table): string
    {
        return "select trim(rdb\$field_name) as \"column_name\" from rdb\$relation_fields where rdb\$relation_name = '$table'";
    }

    /**
     * Compile a create table command.
     */
    public function compileCreate(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        if ($blueprint->temporary) {
            throw new LogicException('This database driver does not support temporary tables.');
        }

        $columns = implode(', ', $this->getColumns($blueprint));

        return 'create table '.$this->wrapTable($blueprint)." ($columns)";
    }

    /**
     * Compile a drop table command.
     */
    public function compileDrop(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        return 'drop table '.$this->wrapTable($blueprint);
    }

    /**
     * Compile a drop table (if exists) command.
     */
    public function compileDropIfExists(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        // Replace the double quotes with single quotes.
        $table = str_replace('"', "'", $this->wrapTable($blueprint));

        return sprintf(
            "execute block as begin if (exists(%s)) then execute statement '%s'; end",
            str_replace('?', $table, $this->compileTableExists(null, $blueprint)), // Replace the ? character with the table name.
            $this->compileDrop($blueprint, $command)
        );
    }

    /**
     * Compile a column addition command.
     */
    public function compileAdd(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        $table = $this->wrapTable($blueprint);

        $columns = $this->prefixArray('ADD', $this->getColumns($blueprint));

        return 'ALTER TABLE '.$table.' '.implode(', ', $columns);
    }

    /**
     * Compile a primary key command.
     */
    public function compilePrimary(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        $columns = $this->columnize($command->columns);

        return 'ALTER TABLE '.$this->wrapTable($blueprint)." ADD PRIMARY KEY ($columns)";
    }

    /**
     * Compile a unique key command.
     */
    public function compileUnique(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        $table = $this->wrapTable($blueprint);

        $index = $this->wrap(substr((string) $command->index, 0, 31));

        $columns = $this->columnize($command->columns);

        return "ALTER TABLE $table ADD CONSTRAINT $index UNIQUE ($columns)";
    }

    /**
     * Compile a plain index key command.
     */
    public function compileIndex(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        $columns = $this->columnize($command->columns);

        $index = $this->wrap(substr((string) $command->index, 0, 31));

        $table = $this->wrapTable($blueprint);

        return "CREATE INDEX $index ON $table ($columns)";
    }

    /**
     * Compile a foreign key command.
     */
    public function compileForeign(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        $table = $this->wrapTable($blueprint);

        $on = $this->wrapTable($command->on);

        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        $columns = $this->columnize($command->columns);

        $onColumns = $this->columnize((array) $command->references);

        $fkName = substr((string) $command->index, 0, 31);

        $sql = "ALTER TABLE $table ADD CONSTRAINT $fkName ";

        $sql .= "FOREIGN KEY ($columns) REFERENCES $on ($onColumns)";

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        if (! is_null($command->onDelete)) {
            $sql .= " ON DELETE $command->onDelete";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " ON UPDATE $command->onUpdate";
        }

        return $sql;
    }

    /**
     * Compile a drop foreign key command.
     */
    public function compileDropForeign(Blueprint|Expression|string $blueprint, Fluent $command): string
    {
        $table = $this->wrapTable($blueprint);

        return "ALTER TABLE $table DROP CONSTRAINT $command->index";
    }

    /**
     * Get the SQL for a character set column modifier.
     */
    protected function modifyCharset(Blueprint|Expression|string $blueprint, Fluent $column): ?string
    {
        if (! is_null($column->charset)) {
            return ' CHARACTER SET '.$column->charset;
        }

        return null;
    }

    /**
     * Get the SQL for a collation column modifier.
     */
    protected function modifyCollate(Blueprint|Expression|string $blueprint, Fluent $column): ?string
    {
        if (! is_null($column->collation)) {
            return ' COLLATE '.$column->collation;
        }

        return null;
    }

    /**
     * Get the SQL for a nullable column modifier.
     */
    protected function modifyNullable(Blueprint|Expression|string $blueprint, Fluent $column): ?string
    {
        return $column->nullable ? '' : ' NOT NULL';
    }

    /**
     * Get the SQL for a default column modifier.
     */
    protected function modifyDefault(Blueprint|Expression|string $blueprint, Fluent $column): ?string
    {
        if (! is_null($column->default)) {
            return ' DEFAULT '.$this->getDefaultValue($column->default);
        }

        return null;
    }

    /**
     * Create the column definition for a char type.
     */
    protected function typeChar(Fluent $column): string
    {
        return "CHAR($column->length)";
    }

    /**
     * Create the column definition for a string type.
     */
    protected function typeString(Fluent $column): string
    {
        return "VARCHAR($column->length)";
    }

    /**
     * Create the column definition for a text type.
     */
    protected function typeText(Fluent $column): string
    {
        return 'BLOB SUB_TYPE TEXT';
    }

    /**
     * Create the column definition for a medium text type.
     */
    protected function typeMediumText(Fluent $column): string
    {
        return 'BLOB SUB_TYPE TEXT';
    }

    /**
     * Create the column definition for a long text type.
     */
    protected function typeLongText(Fluent $column): string
    {
        return 'BLOB SUB_TYPE TEXT';
    }

    /**
     * Create the column definition for an integer type.
     */
    protected function typeInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a big integer type.
     */
    protected function typeBigInteger(Fluent $column): string
    {
        return 'BIGINT';
    }

    /**
     * Create the column definition for a medium integer type.
     */
    protected function typeMediumInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a tiny integer type.
     */
    protected function typeTinyInteger(Fluent $column): string
    {
        return 'SMALLINT';
    }

    /**
     * Create the column definition for a small integer type.
     */
    protected function typeSmallInteger(Fluent $column): string
    {
        return 'SMALLINT';
    }

    /**
     * Create the column definition for a float type.
     */
    protected function typeFloat(Fluent $column): string
    {
        return 'FLOAT';
    }

    /**
     * Create the column definition for a double type.
     */
    protected function typeDouble(Fluent $column): string
    {
        return 'DOUBLE PRECISION';
    }

    /**
     * Create the column definition for a decimal type.
     */
    protected function typeDecimal(Fluent $column): string
    {
        return "DECIMAL($column->total, $column->places)";
    }

    /**
     * Create the column definition for a boolean type.
     */
    protected function typeBoolean(Fluent $column): string
    {
        return 'CHAR(1)';
    }

    /**
     * Create the column definition for an enumeration type.
     */
    protected function typeEnum(Fluent $column): string
    {
        $allowed = array_map(function ($a) {
            return "'".$a."'";
        }, $column->allowed);

        return "VARCHAR(255) CHECK (\"$column->name\" IN (".implode(', ', $allowed).'))';
    }

    /**
     * Create the column definition for a json type.
     */
    protected function typeJson(Fluent $column): string
    {
        return 'VARCHAR(8191)';
    }

    /**
     * Create the column definition for a jsonb type.
     */
    protected function typeJsonb(Fluent $column): string
    {
        return 'VARCHAR(8191) CHARACTER SET OCTETS';
    }

    /**
     * Create the column definition for a date type.
     */
    protected function typeDate(Fluent $column): string
    {
        return 'DATE';
    }

    /**
     * Create the column definition for a date-time type.
     */
    protected function typeDateTime(Fluent $column): string
    {
        return 'TIMESTAMP';
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     */
    protected function typeDateTimeTz(Fluent $column): string
    {
        // No timezone support, default to plain date time
        return $this->typeDateTime($column);
    }

    /**
     * Create the column definition for a time type.
     */
    protected function typeTime(Fluent $column): string
    {
        return 'TIME';
    }

    /**
     * Create the column definition for a time (with time zone) type.
     */
    protected function typeTimeTz(Fluent $column): string
    {
        // No timezone support, default to plain time
        return $this->typeTime($column);
    }

    /**
     * Create the column definition for a timestamp type.
     */
    protected function typeTimestamp(Fluent $column): string
    {
        if ($column->useCurrent) {
            return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        }

        return 'TIMESTAMP';
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
     */
    protected function typeTimestampTz(Fluent $column): string
    {
        // No timezone support, default to plain timestamp
        return $this->typeTimestamp($column);
    }

    /**
     * Create the column definition for a binary type.
     */
    protected function typeBinary(Fluent $column): string
    {
        return 'BLOB SUB_TYPE BINARY';
    }

    /**
     * Create the column definition for a uuid type.
     */
    protected function typeUuid(Fluent $column): string
    {
        return 'CHAR(36)';
    }

    /**
     * Create the column definition for an IP address type.
     */
    protected function typeIpAddress(Fluent $column): string
    {
        return 'VARCHAR(45)';
    }

    /**
     * Create the column definition for a MAC address type.
     */
    protected function typeMacAddress(Fluent $column): string
    {
        return 'VARCHAR(17)';
    }
}
