<?php

namespace DBMaker\ODBC\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class DBMakerBlueprint extends BaseBlueprint
{
    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string  $column
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function increments($column)
    {
        return $this->serial($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string  $column
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function bigIncrements($column)
    {
        return $this->bigserial($column, true);
    }

    /**
     * Create a new serial (4-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function serial($column, $autoIncrement = false)
    {
        return $this->addColumn('serial', $column, compact('autoIncrement'));
    }

    /**
     * Create a new bigserial (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function bigserial($column, $autoIncrement = false)
    {
        return $this->addColumn('bigserial', $column, compact('autoIncrement'));
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param  array|mixed  $columns
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->addCommand('dropColumn', compact('columns'));
    }
}
