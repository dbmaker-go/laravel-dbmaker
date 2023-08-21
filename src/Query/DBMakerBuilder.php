<?php

namespace DBMaker\ODBC\Query;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DBMakerBuilder extends Builder
{
    /** @var int */
    private $DB_IDCap;

    public function __construct(
        ConnectionInterface $connection,
        $grammar = null,
        ?Processor $processor = null
    ) {
        $this->DB_IDCap = $connection->getDB_IDCap() ?? 1;
        parent::__construct($connection, $grammar, $processor);
    }

    public function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $values = is_array(reset($values)) ? $values : [$values];

        foreach ($values as $key => $value) {
            ksort($value);
            $values[$key] = $value;
        }

        $bindings = $this->cleanBindings(array_chunk(Arr::flatten($values, 1), count($values[0])));

        return $this->connection->insert($this->grammar->compileInsert($this, $values), $bindings);
    }

    public function chunkById($count, callable $callback, $column = 'id', $alias = null): bool
    {
        $column = strtoupper($column);
        $alias  = $alias ?: $column;

        do {
            $results = clone $this->forPageAfterId($count, $lastId ?? null, $column)->get();

            if ($results->isEmpty()) {
                break;
            }

            if ($callback($results) === false) {
                return false;
            }

            $lastId = $results->last()->{$alias};
        }
        while ($count == count($results));

        return true;
    }

    public function pluck($column, $key = null): Collection
    {
        $queryResult = $this->onceWithColumns(
            $key ? [$column, $key] : [$column],
            function () {
                return $this->processor->processSelect($this, $this->runSelect());
            }
        );

        if (empty($queryResult)) {
            return collect();
        }

        $column = $this->stripTableForPluck($column);
        $key    = $this->stripTableForPluck($key);

        return is_array($queryResult[0])
            ? $this->pluckFromArrayColumn($queryResult, $column, $key)
            : $this->pluckFromObjectColumn($queryResult, $column, $key);
    }

    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    public function inRandomOrder($seed = ''): self
    {
        return $this;
    }

    public function insertGetId(array $values, $sequence = null): int
    {
        $values = is_array(reset($values)) ? $values : [$values];

        $sql             = $this->grammar->compileInsert($this, $values);
        $processedValues = [];

        foreach ($values as $key => $value) {
            $processedValues[$key] = $this->cleanBindings($value);
        }

        return $this->processor->processInsertGetId($this, $sql, $processedValues, $sequence);
    }

    public function exists(): bool
    {
        $results = $this->connection->select(
            $this->grammar->compileExists($this),
            $this->getBindings(),
            ! $this->useWritePdo
        );

        return isset($results[0]) && (bool) $results['0'];
    }
}
