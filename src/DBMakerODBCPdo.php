<?php

namespace DBMaker\ODBC;

class DBMakerODBCPdo
{
    protected $connection;

    protected $options;

    /**
     * DBMakerODBCPdo constructor.
     */
    public function __construct(string $dsn, string $username, string $passwd, array $options = [])
    {
        $connect = odbc_connect($dsn, $username, $passwd);
        $this->setConnection($connect);
        $this->options = $options;
    }

    public function prepare(string $statement, ?array $driver_options = null): DBMakerODBCPdoStatement
    {
        return new DBMakerODBCPdoStatement($this->connection, $statement, $this->options);
    }

    /**
     * @return bool
     */
    public function exec(string $query)
    {
        return $this->prepare($query)->execute();
    }

    public function beginTransaction(): void
    {
        odbc_autocommit($this->connection, false);
    }

    public function commit(): bool
    {
        return odbc_commit($this->connection);
    }

    public function rollBack(): bool
    {
        $rollback = odbc_rollback($this->connection);
        odbc_autocommit($this->connection, true);

        return $rollback;
    }

    /**
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param  resource  $connection
     */
    protected function setConnection($connection): void
    {
        $this->connection = $connection;
    }
}
