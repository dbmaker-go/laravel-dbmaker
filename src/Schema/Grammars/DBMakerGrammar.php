<?php

namespace DBMaker\ODBC\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;
use RuntimeException;

class DBMakerGrammar extends Grammar
{
    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = [
        'Nullable',
        'Default',
        'After',
        'Before',
        'Increment',
    ];

    /**
     * The possible column serials.
     *
     * @var array
     */
    protected $serials = [
        'serial',
        'bigserial',
    ];

    /**
     * Compile a rename column command.
     */
    public function compileRenameColumn(
        Blueprint $blueprint,
        Fluent $command,
        Connection $connection
    ): string {
        $from = $this->wrapTable($blueprint);

        return "ALTER TABLE {$from} MODIFY ("
            . $this->wrapTable($command->from) . ' NAME TO '
            . $this->wrapTable($command->to) . ')';
    }

    /**
     * Compile a change column command into a series of SQL statements.
     *
     * @throws RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        $columns = $blueprint->getColumns();

        return collect($columns)->map(function ($column) use ($blueprint) {
            $columnName      = $this->wrap($column);
            $columnType      = $this->getType($column);
            $columnModifiers = $this->addModifiers('', $blueprint, $column);

            return sprintf(
                'ALTER TABLE %s MODIFY (%s TO %s %s %s)',
                $this->wrapTable($blueprint),
                $columnName,
                $columnName,
                $columnType,
                $columnModifiers
            );
        })->all();
    }

    /**
     * Compile a rename table name command.
     */
    public function compileRename(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf(
            'ALTER TABLE %s RENAME TO %s',
            $this->wrapTable($blueprint),
            $this->wrapTable($command->to)
        );
    }

    /**
     * Compile a rename index command.
     */
    public function compileRenameIndex(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf(
            'ALTER INDEX %s ON %s RENAME TO %s',
            $this->wrap($command->from),
            $this->wrapTable($blueprint),
            $this->wrap($command->to)
        );
    }

    /**
     * Compile the SQL needed to drop all tables.
     */
    public function compileDropAllTables(array $tables): string
    {
        $wrappedTables = array_map([$this, 'wrapTable'], $tables);

        return 'DROP TABLE ' . implode(', ', $wrappedTables);
    }

    /**
     * Compile the SQL needed to drop all views.
     */
    public function compileDropAllViews(array $views): string
    {
        $wrappedViews = array_map([$this, 'wrapTable'], $views);

        return 'DROP VIEW ' . implode(', ', $wrappedViews);
    }

    /**
     * Compile the SQL needed to retrieve all view names.
     */
    public function compileGetAllViews(): string
    {
        return "SELECT TABLE_NAME FROM systable WHERE TABLE_TYPE = 'VIEW'";
    }

    /**
     * Compile the SQL needed to retrieve all table names.
     */
    public function compileGetAllTables(): string
    {
        return 'SELECT TABLE_NAME FROM SYSTABLE';
    }

    /**
     * Compile a drop table command.
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command): string
    {
        return 'DROP TABLE ' . $this->wrapTable($blueprint);
    }

    /**
     * Compile alter table commands for adding columns.
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command): array
    {
        $columns = $this->prefixArray('add column', $this->getColumns($blueprint));

        return collect($columns)->map(function (string $column) use ($blueprint) {
            return 'ALTER TABLE ' . $this->wrapTable($blueprint) . ' ' . $column;
        })->all();
    }

    /**
     * Compile a drop column command.
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command): array
    {
        $columns = $this->prefixArray('drop', $this->wrapArray($command->columns));

        return collect($columns)->map(function (string $column) use ($blueprint) {
            return 'ALTER TABLE ' . $this->wrapTable($blueprint) . ' ' . $column;
        })->all();
    }

    /**
     * Compile the query to determine the list of tables.
     */
    public function compileTableExists(): string
    {
        return 'SELECT * FROM SYSTABLE WHERE TABLE_NAME = ?';
    }

    /**
     * Compile the query to get all column names.
     */
    public function compileGetAllColumns(): string
    {
        return 'SELECT COLUMN_NAME FROM SYSCOLUMN WHERE TABLE_NAME = ?';
    }

    /**
     * Compile a drop table (if exists) command.
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command): string
    {
        return "DROP TABLE IF EXISTS {$this->wrapTable($blueprint)}";
    }

    /**
     * Compile a drop primary key command.
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command): string
    {
        return "ALTER TABLE {$this->wrapTable($blueprint)} DROP PRIMARY KEY";
    }

    /**
     * Compile a drop unique key command.
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command): string
    {
        $index = $this->wrap($command->index);

        return "DROP INDEX {$index} FROM {$this->wrapTable($blueprint)}";
    }

    /**
     * Compile a drop index command.
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command): string
    {
        $index = $this->wrap($command->index);

        return "DROP INDEX {$index} FROM {$this->wrapTable($blueprint)}";
    }

    /**
     * Compile a primary key command.
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command): string
    {
        $command->name(null);

        return $this->compileKey($blueprint, $command, 'primary key');
    }

    /**
     * Compile a unique key command.
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command): string
    {
        return $this->compileKey($blueprint, $command, 'unique');
    }

    /**
     * Compile a plain index key command.
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command): string
    {
        return $this->compileKey($blueprint, $command, 'index');
    }

    /**
     * Compile an index creation command.
     */
    protected function compileKey(Blueprint $blueprint, Fluent $command, string $type): string
    {
        $indexName = $this->wrap($command->index);
        $table     = $this->wrapTable($blueprint);
        $columns   = $this->columnize($command->columns);

        switch ($type) {
            case 'index':
                return "CREATE INDEX {$indexName} ON {$table} ({$columns})";
            case 'unique':
                return "CREATE UNIQUE INDEX {$indexName} ON {$table} ({$columns})";

            default:
                return "ALTER TABLE {$table} ADD {$type} ({$columns})";
        }
    }

    /**
     * Compile a drop foreign key command.
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command): string
    {
        $index = $this->wrap($command->index);

        return "ALTER TABLE {$this->wrapTable($blueprint)} DROP FOREIGN KEY {$index}";
    }

    /**
     * Compile a create table command.
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection): string
    {
        return $this->compileCreateTable($blueprint, $command, $connection);
    }

    /**
     * Create the main create table clause.
     */
    protected function compileCreateTable(Blueprint $blueprint, Fluent $command, Connection $connection): string
    {
        $tableType    = $blueprint->temporary ? 'CREATE TEMPORARY' : 'CREATE';
        $tableColumns = implode(', ', $this->getColumns($blueprint));

        return "{$tableType} TABLE {$this->wrapTable($blueprint)} ({$tableColumns})";
    }

    /**
     * Get the SQL for a default column modifier.
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column): ?string
    {
        if ($column->default !== null) {
            return ' DEFAULT ' . $this->getDefaultValue($column->default);
        }

        return null;
    }

    /**
     * Format a value so that it can be used in "default" clauses.
     */
    protected function getDefaultValue($value): string
    {
        if ($value instanceof Expression) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return (string) (int) $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return "'$value'";
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column): ?string
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' primary key';
        }

        return null;
    }

    /**
     * Create the column definition for a string type.
     */
    protected function typeString(Fluent $column): string
    {
        return "varchar({$column->length})";
    }

    /**
     * Create the column definition for a big integer type.
     */
    protected function typeBigInteger(): string
    {
        return 'bigint';
    }

    /**
     * Create the column definition for an integer type.
     */
    protected function typeInteger(): string
    {
        return 'int';
    }

    /**
     * Create the column definition for an serial type.
     */
    protected function typeSerial(): string
    {
        return 'serial';
    }

    /**
     * Create the column definition for a bigserial type.
     */
    protected function typeBigserial(): string
    {
        return 'bigserial';
    }

    /**
     * Create the column definition for a binary type.
     */
    protected function typeBinary(): string
    {
        return 'blob';
    }

    /**
     * Create the column definition for a boolean type.
     */
    protected function typeBoolean(Fluent $column): string
    {
        return $this->typeSmallInteger($column);
    }

    /**
     * Create the column definition for a char type.
     */
    protected function typeChar(Fluent $column): string
    {
        return "char({$column->length})";
    }

    /**
     * Create the column definition for a date type.
     */
    protected function typeDate(): string
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     */
    protected function typeDateTime(Fluent $column): string
    {
        return $this->typeTimestamp($column);
    }

    /**
     * Create the column definition for a decimal type.
     */
    protected function typeDecimal(Fluent $column): string
    {
        return "decimal({$column->total},{$column->places})";
    }

    /**
     * Create the column definition for a double type.
     */
    protected function typeDouble(): string
    {
        return 'Double';
    }

    /**
     * Create the column definition for an enumeration type.
     */
    protected function typeEnum(Fluent $column): string
    {
        return sprintf('varchar(255) check ("%s" in (%s))', $column->name, $this->quoteString($column->allowed));
    }

    /**
     * Create the column definition for a float type.
     */
    protected function typeFloat(Fluent $column): string
    {
        if ($column->total) {
            return "Float({$column->total})";
        }

        return 'Float';
    }

    /**
     * Create the column definition for a json type.
     */
    protected function typeJson(): string
    {
        return 'JSONCOLS';
    }

    /**
     * Create the column definition for a jsonb type.
     */
    protected function typeJsonb(): string
    {
        return 'JSONCOLS';
    }

    /**
     * Create the column definition for a long text type.
     */
    protected function typeLongText(): string
    {
        return 'long varchar';
    }

    /**
     * Create the column definition for a medium integer type.
     */
    protected function typeMediumInteger(): string
    {
        return 'integer';
    }

    /**
     * Create the column definition for a medium text type.
     */
    protected function typeMediumText(): string
    {
        return 'long varchar';
    }

    /**
     * Create the column definition for a timestamp type.
     */
    protected function typeTimestamp(Fluent $column): string
    {
        return $column->useCurrent
            ? 'TIMESTAMP default CURRENT_TIMESTAMP'
            : 'TIMESTAMP';
    }

    /**
     * Create the column definition for a small integer type.
     */
    protected function typeSmallInteger(): string
    {
        return 'smallint';
    }

    /**
     * Create the column definition for a tiny integer type.
     */
    protected function typeTinyInteger(Fluent $column): string
    {
        return $this->typeSmallInteger();
    }

    /**
     * Create the column definition for a text type.
     */
    protected function typeText(): string
    {
        return 'long varchar';
    }

    /**
     * Create the column definition for a time type.
     */
    protected function typeTime(): string
    {
        return 'time';
    }

    /**
     * Get the SQL for a nullable column modifier.
     */
    protected function modifyNullable(Fluent $column): string
    {
        if (in_array($column->type, ['serial', 'bigserial', 'json', 'jsonb'])) {
            return '';
        }

        return $column->nullable ? '' : ' not null';
    }

    /**
     * Get the SQL for an "after" column modifier.
     */
    protected function modifyAfter(Fluent $column): ?string
    {
        if ($column->after !== null) {
            return ' after ' . $this->wrap($column->after);
        }

        return null;
    }

    /**
     * Get the SQL for a "before" column modifier.
     */
    protected function modifyBefore(Fluent $column): ?string
    {
        if ($column->before !== null) {
            return ' before ' . $this->wrap($column->before);
        }

        return null;
    }

    /**
     * Compile the command to enable foreign key constraints.
     */
    public function compileEnableForeignKeyConstraints(): string
    {
        return "CALL SETSYSTEMOPTION('FKCHK','1');";
    }

    /**
     * Compile the command to disable foreign key constraints.
     */
    public function compileDisableForeignKeyConstraints(): string
    {
        return "CALL SETSYSTEMOPTION('FKCHK','0');";
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     */
    protected function typeDateTimeTz(Fluent $column): string
    {
        return $this->typeDateTime($column);
    }

    /**
     * Create the column definition for a time (with time zone) type.
     */
    protected function typeTimeTz(Fluent $column): string
    {
        return $this->typeTime($column);
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
     */
    protected function typeTimestampTz(Fluent $column): string
    {
        return $this->typeTimestamp($column);
    }

    /**
     * Create the column definition for a uuid type.
     */
    protected function typeUuid(Fluent $column): string
    {
        return 'char(36)';
    }

    /**
     * Create the column definition for an IP4 or IPV6 address type.
     */
    protected function typeIpAddress(Fluent $column): string
    {
        return 'varchar(45)';
    }

    /**
     * Create the column definition for a MAC address type.
     */
    protected function typeMacAddress(Fluent $column): string
    {
        return 'varchar(17)';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     */
    public function typeGeometry(Fluent $column): string
    {
        return 'varchar(128)';
    }

    /**
     * Create the column definition for a spatial Point type.
     */
    public function typePoint(Fluent $column): string
    {
        return 'varchar(128)';
    }

    /**
     * Create the column definition for a spatial LineString type.
     */
    public function typeLineString(Fluent $column): string
    {
        return 'LONG VARCHAR';
    }

    /**
     * Create the column definition for a spatial Polygon type.
     */
    public function typePolygon(Fluent $column): string
    {
        return 'LONG VARCHAR';
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
     */
    public function typeGeometryCollection(Fluent $column): string
    {
        return 'LONG VARCHAR';
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
     */
    public function typeMultiPoint(Fluent $column): string
    {
        return 'LONG VARCHAR';
    }

    /**
     * Create the column definition for a spatial MultiLineString type.
     */
    public function typeMultiLineString(Fluent $column): string
    {
        return 'LONG VARCHAR';
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
     */
    public function typeMultiPolygon(Fluent $column): string
    {
        return 'LONG VARCHAR';
    }

    /**
     * Create the column definition for a generated, computed column type.
     *
     * @throws RuntimeException
     */
    protected function typeComputed(Fluent $column): void
    {
        throw new RuntimeException('This database driver requires a type, see the virtualAs / storedAs modifiers.');
    }
}
