<?php

namespace DBMaker\ODBC;

use PDO;
use PDOStatement;

class DBMakerPdo extends PDO
{
    /** @var PDO */
    protected $pdo;

    /**
     * Get the column listing for a given table.
     *
     * @param  array  $options
     */
    public function __construct(string $dsn, string $username, string $password, $options = [])
    {
        $options = array_replace($options, [
            PDO::ATTR_EMULATE_PREPARES   => false, // Disable emulated prepares for security reasons
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exception mode
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Set default fetch mode
        ]);

        if (isset($options['dbidcap']) && $options['dbidcap'] == 1) {
            $options[PDO::ATTR_CASE] = PDO::CASE_LOWER;
        }

        parent::__construct($dsn, $username, $password, $options);
        $pdo = new PDO($dsn, $username, $password, $options);
        $this->setConnection($pdo);
    }

    /**
     * Prepare a statement.
     *
     * @param  string  $statement
     * @param  array  $options
     */
    public function prepare($statement, $options = []): false|PDOStatement
    {
        return $this->getConnection()->prepare($statement, $options);
    }

    /**
     * Get the underlying PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Set the underlying PDO connection.
     */
    public function setConnection(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }
}
