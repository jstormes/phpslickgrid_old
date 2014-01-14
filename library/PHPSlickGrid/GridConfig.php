<?php

class PHPSlickGrid_GridConfig {

    public $data       = array();
    public $conditions = array();
    public $plugins    = array();
    public $staticFields = array(); 
    public $join		= array();

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        //echo "Getting '$name'\n";
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
        'Undefined property via __get(): ' . $name .
        ' in ' . $trace[0]['file'] .
        ' on line ' . $trace[0]['line'],
        E_USER_NOTICE);
        return null;
    }
     
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
    
    public function JoinTo($join_to_table) {
    	if (is_object($join_to_table)) {
    		$this->join[] = get_class($join_to_table);
    	}
    	else {
    		$this->join[] = $join_to_table;
    	}
    		
    }

}