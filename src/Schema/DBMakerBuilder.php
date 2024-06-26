<?php
/**
 * Created by syscom.
 * User: syscom
 * Date: 17/06/2019
 * Time: 15:50
 */

namespace DBMaker\ODBC\Schema;

use Closure;
use RuntimeException;
use Illuminate\Database\Schema\Builder;
use DBMaker\ODBC\DBMakerConnection as Connection;
use DBMaker\ODBC\Schema\DBMakerBlueprint as Blueprint;

class DBMakerBuilder extends Builder {
	
	/**
	 * Create a new command set with a Closure.
	 *
	 * @param  string  $table
	 * @param  \Closure|null  $callback
	 * @return \Illuminate\Database\Schema\Blueprint
	 */
	protected function createBlueprint($table,Closure $callback = null) {
		$prefix = $this->connection->getConfig('prefix_indexes') ? 
		$this->connection->getConfig('prefix') : '';
		if(isset($this->resolver)) {
			return call_user_func($this->resolver,$table,$callback,$prefix);
		}
		return new Blueprint($table,$callback,$prefix);
	}
	
    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table) {
        $table = $this->connection->getTablePrefix().$table;
        return count($this->connection->select(
               $this->grammar->compileTableExists(),[$table]
         )) > 0;
    }
    
    /**
     * check column is in table or not.
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table,$column) { 
        return count($this->connection->select("select * from SYSCOLUMN where COLUMN_NAME='"
    	 	    .$column."' and TABLE_NAME='".$table."';"))==1 ? true:false;
   }
    
    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table) {
        $table = $this->connection->getTablePrefix().$table;
        return $this->connection->select($this->grammar->compileGetAllColumns($table));
	}	 
	
	/**
	 * Drop all tables from the database.
	 *
	 * @return void
	 */
	public function dropAllTables() {
		$keyObj = $this->connection->select("select fk_tbl_name,fk_name from sysforeignkey;");
		foreach($keyObj as $obj) {
			$this->connection->statement('ALTER TABLE "'
											.$obj->FK_TBL_NAME . '" DROP FOREIGN KEY "'
											.$obj->FK_NAME.'";' );
		}
		$tables = [];
		foreach($this->getAllTables() as $row) {
			$row = (array)$row;
			$tables[] = reset($row);
		}
		if(empty($tables)) {
			return;
		}
		foreach($tables as $key => $table){
       		$this->connection->statement($this->grammar->compileDropAllTables($table));
        }
    }
    
    /**
     * Compile the SQL needed to retrieve all table names.
     *
     * @return string
     */
    public function getAllTables() {
    	 return $this->connection->select($this->grammar->compileGetAllTables());
	}
    
    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews() {
    	$views = [];
       foreach ($this->getAllViews() as $row) {
          $row = (array)$row;
          $views[] = reset($row);
        }
       if(empty($views)) {
          return;
        }
       foreach($views as $key => $view){
        $this->connection->statement($this->grammar->compileDropAllViews($view));
        }
    }
    
    /**
     * Compile the SQL needed to retrieve all table names.
     *
     * @return string
     */
    public function getAllViews() {
    	return $this->connection->select($this->grammar->compileGetAllViews());
    }
    
    /**
     * Get the data type for the given column name.
     *   no one function call getColumnType
     *
     * @param  string  $table
     * @param  string  $column
     * @return string  DBMaker column type
     */
    public function getColumnType($table, $column, $fullDefinition = false) {
    	$sql = "SELECT TYPE_NAME FROM SYSCOLUMN WHERE TABLE_NAME = '".$table."' AND COLUMN_NAME = '".$column."'";
    	$result = $this->connection->select($sql);
    	$TYPE_NAME = $result['0']['TYPE_NAME'];    	
    	if($TYPE_NAME === ""){
    		throw new RuntimeException('DBMaker can\'t get Column Type');	
    	}
    	else if($TYPE_NAME == "jsoncols"){	
    		return "dynamic";
    	}
    	else{
    		/*
    		 * DBMaker Column type and Laravel Column type mapping  not yet implemented
    		 *
    		 * */
    		return $TYPE_NAME;
    	} 	 
    }  
}
