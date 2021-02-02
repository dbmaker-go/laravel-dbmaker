<?php

/**
 * Created by syscom.
 * User: syscom
 * Date: 17/06/2019
 * Time: 15:50
 */
namespace DBMaker\ODBC;

use PDOStatement;

class DBMakerODBCPdoStatement extends PDOStatement {

    protected $query;
	protected $options;
	protected $params = [];
	protected $statement;

	/**
	 *
	 * @param resource $conn
	 * @param string $query
	 */
	public function __construct($conn, $query, $options = []) {
		$this->query = preg_replace('/(?<=\s|^):[^\s:]++/um','?',$query);
		$this->params = $this->getParamsFromQuery($query);
		$this->statement = odbc_prepare($conn,$this->query);
		$this->options = $options;
	}

    /**
     * get Params From Query String
     *
     * @param $query
     * @return array
     */
	protected function getParamsFromQuery($query): array
    {
		$params = [];
		$qryArray = explode(" ",$query);
		$i = 0;
		while(isset($qryArray[$i])) {
			if(preg_match ( "/^:/",$qryArray[$i]))
				$params[$qryArray[$i]] = null;
			$i++;
		}
		return $params;
	}
	
	/**
	 *
	 * @return int
	 */
	public function rowCount(): int {
		return odbc_num_rows($this->statement);
	}

	/**
	 *
	 * @param string $param
	 * @param string $val
	 * @param string $ignore
	 * @return void
	 */
	public function bindValue($param, $val, $ignore = null): void {
		$this->params[$param] = $val;
	}
	
	/**
	 *
	 * @param array $ignore        	
	 * @return void
	 */
	public function execute($ignore = null): void {
		odbc_execute($this->statement,$this->params);
		$this->params = [];
	}

    /**
     * @param null $how
     * @param null $class_name
     * @param null $ctor_args
     * @return array
     */
	public function fetchAll($how = null, $class_name = null, $ctor_args = null): array {
		$records = [];
		while($record = $this->fetch()) {
			$records [] = $record;
		}
		if (isset($this->options['idcap']) && $this->options['idcap'] == 1) $this->keysToLower($records);
		return $records;
	}
	
	/**
	 * Fetch an associative array from an ODBC query.
	 *
	 * @param array $option        	
	 * @param array $ignore        	
	 * @param array $ignore2        	
	 * @return array|false|object
	 */
	public function fetch($option = null, $ignore = null,$ignore2 = null) {
		return odbc_fetch_object($this->statement);
	}

    /**
     * @param $obj
     * @return mixed|object
     */
	function &keysToLower(&$obj) {
		if (is_object($obj)) {
			$newobj = (object)array();
			foreach($obj as $key => &$val)
				$newobj->{strtolower($key)} = $this->keysToLower($val);
			$obj = $newobj;
		} else if(is_array($obj))
			foreach($obj as &$value)
				$this->keysToLower($value);
		return $obj;
	}
}