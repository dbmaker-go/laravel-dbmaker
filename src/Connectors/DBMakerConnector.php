<?php

namespace DBMaker\ODBC\Connectors;

use DBMaker\ODBC\DBMakerODBCPdo;
use DBMaker\ODBC\DBMakerPdo;
use Exception;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use PDO;

class DBMakerConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @throws Exception
     */
    public function connect(array $config): PDO
    {
        $options = $this->getOptions($config);
        $dsn     = $this->getDsn($config);

        return $this->createConnection($dsn, $config, $options);
    }

    /**
     * Create a new PDO connection.
     *
     * @param  string  $dsn
     *
     * @throws Exception
     */
    public function createConnection($dsn, array $config, array $options): PDO
    {
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        try {
            return $this->createPdoConnection("odbc:" .$dsn, $username, $password, $options);
        }
        catch (Exception $e) {
            return new DBMakerODBCPdo($dsn, $username, $password, $options);
        }
    }

    /**
     * Create a new PDO connection instance.
     *
     * @param  string  $dsn
     * @param  string|null  $username
     * @param  string|null  $password
     * @param  array  $options
     */
    protected function createPdoConnection($dsn, $username, $password, $options): PDO
    {
        return new DBMakerPdo($dsn, $username, $password, $options);
    }

    /**
     * Create a DSN string from a configuration.
     */
    protected function getDsn(array $config): string
    {
        return $config['dsn'] ?? '';
    }
}
