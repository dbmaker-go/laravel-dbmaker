<?php

namespace DBMaker\ODBC\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;

class DBMakerProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     *
     * @return int|string
     */
    public function processInsertGetId(
        Builder $query,
        $sql,
        $values,
        $sequence = null
    ) {
        // Insert the data using the provided SQL and values
        $query->getConnection()->insert($sql, $values);

        // Retrieve the last inserted ID from 'sysconinfo' table
        $id = $query->getConnection()->table('sysconinfo')->max('LAST_SERIAL');

        // Return the ID. If it's numeric, cast it to an integer
        return is_numeric($id) ? (int) $id : $id;
    }
}
