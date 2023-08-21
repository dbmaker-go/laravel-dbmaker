<?php

namespace DBMaker\ODBC;

use PDO;
use PDOStatement;

class DBMakerODBCPdoStatement extends PDOStatement
{
    /** @var string */
    protected $query;

    /** @var array */
    protected $options;

    /** @var array */
    protected $params = [];

    /** @var resource */
    protected $statement;

    /**
     * Constructor.
     *
     * @param  resource  $conn
     * @param  string  $query
     * @param  array  $options
     */
    public function __construct($conn, $query, $options = [])
    {
        $this->query     = $this->replaceNamedPlaceholders($query);
        $this->params    = $this->extractNamedPlaceholders($query);
        $this->statement = odbc_prepare($conn, $this->query);
        $this->options   = $options;
    }

    /**
     * Replace named placeholders with ?.
     */
    protected function replaceNamedPlaceholders(string $query): string
    {
        return preg_replace('/(?<=\s|^):[^\s:]++/um', '?', $query);
    }

    /**
     * Extract named placeholders from query.
     */
    protected function extractNamedPlaceholders(string $query): array
    {
        $params = [];
        $parts  = explode(' ', $query);

        foreach ($parts as $part) {
            if (preg_match('/^:/', $part)) {
                $params[$part] = null;
            }
        }

        return $params;
    }

    /**
     * Get row count.
     */
    public function rowCount(): int
    {
        return odbc_num_rows($this->statement);
    }

    /**
     * Bind value.
     *
     * @return void
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR): bool
    {
        $this->params[$parameter] = $value;

        return true;
    }

    /**
     * Execute statement.
     *
     * @return void
     */
    public function execute($input_parameters = null): bool
    {
        odbc_execute($this->statement, $this->params);
        $this->params = [];

        return true;
    }

    /**
     * Fetch all results.
     */
    public function fetchAll($fetch_style = PDO::FETCH_BOTH, $fetch_argument = null, $ctor_args = []): array
    {
        $records = [];

        while ($record = $this->fetch()) {
            $records[] = $record;
        }

        if (isset($this->options['idcap']) && $this->options['idcap'] == 1) {
            $this->convertKeysToLower($records);
        }

        return $records;
    }

    /**
     * Fetch a record.
     *
     * @return array|false|object
     */
    public function fetch($fetch_style = PDO::FETCH_BOTH, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return odbc_fetch_object($this->statement);
    }

    /**
     * Convert object/array keys to lowercase.
     */
    protected function convertKeysToLower(&$data)
    {
        if (is_object($data)) {
            $newObj = (object) [];

            foreach ($data as $key => &$val) {
                $newObj->{strtolower($key)} = $this->convertKeysToLower($val);
            }
            $data = $newObj;
        }
        elseif (is_array($data)) {
            foreach ($data as &$value) {
                $this->convertKeysToLower($value);
            }
        }

        return $data;
    }
}
