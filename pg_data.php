<?php

//data object so we can alter some calls
//implements array access so we can call them by lowercase
//implements jsonserializable so we can specify the container as the json array
class PG_Data implements ArrayAccess, JsonSerializable, Iterator {
    private $container = array();
	
	public static function getInst($row){
		return new PG_Data($row);
	}
	
    public function __construct(&$bl) {
        $this->container = $bl;
    }
	
	/*Array access funcs */
    public function offsetSet($offset, $value) {
    	$offset = strtolower($offset);
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }
	
    public function offsetExists($offset) {
    	$offset = strtolower($offset);
        return isset($this->container[$offset]);
    }
	
    public function offsetUnset($offset) {
    	$offset = strtolower($offset);
        unset($this->container[$offset]);
    }
	
    public function offsetGet($offset) {
    	$offset = strtolower($offset);
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
	
	/* Json func */
	public function jsonSerialize(){
		return $this->container;
	}
	
	/* Iterator funcs */
	public function rewind()
    {
        return reset($this->container);
    }
  
    public function current()
    {
        return current($this->container);
    }
  
    public function key() 
    {
        return key($this->container);
    }
  
    public function next() 
    {
        return next($this->container);
    }
  
    public function valid()
    {
        $key = key($this->container);
        return ($key !== NULL && $key !== FALSE);
    }
	
}
