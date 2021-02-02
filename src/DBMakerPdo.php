<?php

/**
 * Created by syscom.
 * User: syscom
 * Date: 17/06/2019
 * Time: 15:50
 */
namespace DBMaker\ODBC;

use PDO;
use PDOStatement;

class DBMakerPdo extends PDO {

    /**
     * @var $pdo PDO
     */
    protected $pdo;

    /**
     * Get the column listing for a given table.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public function __construct(string $dsn, string $username, string $password, $options = [])
    {
        if (isset($options['dbidcap']) && $options['dbidcap'] == 1) $options[PDO::ATTR_CASE] = PDO::CASE_LOWER;
        parent :: __construct($dsn, $username, $password, $options);
        $pdo = new PDO($dsn, $username, $password, $options);
        $this->setConnection($pdo);
    }

    /**
     *
     * @param  string  $statement
     * @param  array  $options
     * @return DBMakerPdo|false|PDOStatement
     */
    public function prepare($statement, $options = []) {
        return parent::prepare($statement);
    }

    /**
     * @return mixed
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param $pdo
     * @return void
     */
    public function setConnection($pdo) {
        $this->pdo = $pdo;
    }
}