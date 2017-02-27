<?php
require_once 'pg_res.php';

/*
Class for prepared statements
*/
class PG_Prepared{
	private $query, $conObj, $vars, $varCount, $result, $stmtName, $binds, $row;
	public $insert_id;
	
	public function __construct($passedConObj, $queryStr){
		$this->varCount = substr_count($queryStr, '?');
		$this->query = $this->formatQuery($queryStr);	
		$this->conObj = $passedConObj;
		$this->stmtName = uniqid(session_id());	//generate unique code for prep stmt
		
		pg_prepare($this->conObj->con, $this->stmtName, $this->query);
	}
	
	public static function getInst($passedConObj, $queryStr){
		return new PG_Prepared($passedConObj, $queryStr);
	}
	
	public function bind_param($types, &...$vars){
		$this->vars = array();	//reset
		
		$tz = date('O');
		$count = 0;		
		for($i = 0; $i < count($vars); $i++){
			
			//if the string is a datetime then we convert it to have a timezone if it doesn't already
			$vars[$i] = preg_replace("/^(\s*(\d{4}\-\d{2}\-\d{2})(\s+\d{2}\:\d{2}\:\d{2}(\.\d*)?)?\s*)$/", "$1 ".$tz, $vars[$i]);
			
			$this->vars[$i] = & $vars[$i];
		}
		
		if(count($vars) < 2){
			return false;
		}
		
		//make sure variable counts match
		if(strlen($types) != (count($vars)) ||
			strlen($types) != $this->varCount){
			return false;	//error
		}
		
		return $this;
		
	}
	
	public function execute(){
		if(count($this->vars) < 1){
			$this->result = PG_Res::getInst(pg_query($this->conObj->con, $this->query));
		}else{
			$this->result = PG_Res::getInst(pg_execute($this->conObj->con, $this->stmtName, $this->vars));
		}
		if($this->conObj->checkIfInsert($this->query)){
			$this->insert_id = $this->conObj->getLastID();
		}else{
			$this->insert_id = null;
		}
		return $this->result;
	}
	
	//prepares the query string by replacing ?'s with $# vars
	private function formatQuery($queryStr){
		$queryStr = strtolower($queryStr);
		for($i = 0; $i < $this->varCount; $i++){
			preg_match('/\?/', $queryStr, $matches, PREG_OFFSET_CAPTURE);
			$queryStr = substr_replace($queryStr, '$'.($i+1), $matches[0][1], 1);
		}
		if(substr_count($queryStr, '?') > 0){
			return false;	//error
		}
		return $queryStr;
	}
	
	public function __toString() {
        return 'Prepared statement';
    }
	
	public function bind_result(&...$resultVars){
		$this->binds = array();
		$count = 0;
		for($i = 0; $i < count($resultVars); $i++){
			$this->binds[$i] = & $resultVars[$i];
		}
	}
	
	public function fetch(){
		$this->row = $this->result->fetch_array();
		
		if(!($this->row)){
			return false;
		}
		
		for($i = 0; $i < count($this->binds); $i++){
			$this->binds[$i] = $this->row[$i];
		}
		return true;
	}
	
	public function close(){
		return true;
	}
}