<?php
require_once 'pg_data.php';

/*
Results class
*/
class PG_Res {
	private $result;
	public $num_rows;
	
	public function __construct($res){
		$this->result = $res;
		if(!($this->result)){
			return pg_result_error($this->result);	//error
		}
		$this->num_rows = pg_num_rows($this->result);
	}
	
	public static function getInst($res){
		return new PG_Res($res);
	}
	
	public function fetch_array(){
		if(!$this->result){
			return false;
		}
		$fetched = pg_fetch_array($this->result);
		if(!$fetched){
			return $fetched;
		}
		return PG_Data::getInst($fetched);
	}
	
	public function fetch_assoc(){
		if(!$this->result){
			return false;
		}
		$fetched = pg_fetch_assoc($this->result);
		if(!$fetched){
			return $fetched;
		}
		return PG_Data::getInst($fetched);
	}
	
	public function __toString() {
        return 'Result set';
    }
	
	public function data_seek($offset){
		return pg_result_seek($this->result, $offset);
	}
	
}