<?php

namespace DBMaker\ODBC\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use RuntimeException;

class DBMakerGrammar extends Grammar
{
    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     *
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RAND(' . $seed . ')';
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function wrapValue($value)
    {
        return $value === '*' ? $value : str_replace('`', '``', $value);
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        $table = $this->wrapTable($query->from);

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        $columns    = $this->columnize(array_keys(reset($values)));
        $parameters = '(' . $this->parameterize($values[0]) . ')';

        return "insert into $table($columns) values $parameters";
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  array  $aggregate
     *
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        if ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as "aggregate"';
    }

    /**
     * Compile a where exists clause.
     *
     * @param  array  $where
     *
     * @return string
     */
    protected function whereExists(Builder $query, $where)
    {
        return 'exists (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile an exists statement into SQL.
     *
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $select = $this->compileSelect($query);

        return "select case when CONNECTION_ID is not null then 1 else 0 
                end from SYSCONINFO where exists ($select)";
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string  $name
     *
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TO ' . $name;
    }

    /**
     * Compile a "JSON length" statement into SQL.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     *
     * @throws RuntimeException
     *
     * @return string
     */
    protected function compileJsonLength($column, $operator, $value)
    {
        throw new RuntimeException('DBMaker does not support JSON length operations.');
    }

    /**
     * Compile a "JSON contains" statement into SQL.
     *
     * @param  string  $column
     * @param  string  $value
     *
     * @throws RuntimeException
     *
     * @return string
     */
    protected function compileJsonContains($column, $value)
    {
        throw new RuntimeException('DBMaker does not support JSON contains operations.');
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        return ['delete from ' . $this->wrapTable($query->from) => []];
    }
}
