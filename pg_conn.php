<?php

require_once 'pg_res.php';
require_once 'pg_prepared.php';

/**
   * Class to open connection to pgsql
   * This mimic's mysqli functions for easier migration
   * 
   * @package    pgsqli
   * @author     Garrett Allen
   */
class PG_Conn{
	public $insert_id, $con;
	
	public function __construct($host, $port, $user, $pass, $db=''){
		$conStr = "host={$host} port={$port} user={$user} password={$pass}";
		if($db != ''){
			$conStr .= " dbname={$db}";
		}
		$this->con = pg_connect($conStr);
	}
	
	public function __toString() {
        return (String)$this->con;
    }
	
	public function query($queryStr){
		$convertQuery = $this->convertQuery($queryStr);
		$res = PG_Res::getInst(pg_query($this->con, $convertQuery));
		
		
		if($this->checkIfInsert($convertQuery)){
			$this->getLastID();
		}
		
		return $res;
	}
	
	public function prepare($queryStr){
		return PG_Prepared::getInst($this, $this->convertQuery($queryStr));
	}
	
	public function close(){
		return pg_close($this->con);
	}
	
	public function escape_string($str){
		return pg_escape_string($this->con, $str);
	}
	
	public function real_escape_string($str){
		return $this->escape_string($str);
	}
	
	//checks if the query is an insert query or not
	public static function checkIfInsert($query){
		preg_match("/INSERT\sINTO/i", $query, $match);
		if(count($match) > 0){
			return true;
		}
		return false;
	}
	
	public function getLastID(){
		$res = PG_Res::getInst(pg_query($this->con, $this->convertQuery('SELECT LASTVAL();')));
		if($dat = $res->fetch_assoc()){
			$this->insert_id = $dat['lastval'];
			return $dat['lastval'];
		}
		$this->insert_id = null;
		return false;
	}
	
	//converts typical mysql statements to postgres safe
	public function convertQuery($query){
		$query = strtolower($query);
		$query = preg_replace('/([^\s\(\)]+)\sLIKE/i', 'CAST(${1} AS TEXT) ILIKE', $query);	//convert from mysql to pg format
		$query = preg_replace('/if\(([^,]*),([^,]*)\s*,([^,)]*)\s*\)/i', 'CASE WHEN (${1}) THEN ${2} ELSE ${3} END', $query);
		$query = preg_replace('/ifnull/i', 'COALESCE', $query);
		
		
		//converts timestamps to have a timezone on the end if they don't already
		$tz = date('O');
		$query = preg_replace("/((\d{4}\-\d{2}\-\d{2})(\s+\d{2}\:\d{2}\:\d{2}(\.\d*)?)?)(\s*[\'\"])/", "$1 ".$tz."$5", $query);
		
		return $query;
	}
	
}


