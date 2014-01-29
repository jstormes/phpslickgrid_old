<?php

/**
 * GapDataCache - Feeds blocks to the gapdatacache.js library on the browser
 * filtered by static filters passed to it via session data and dynamic 
 * filters passed to it from the browser.  It can also add and update records
 * passed back to it from the browser.  In addition it applies sorts to 
 * the data.
 * 
 * This version can join tables/grids across a data set using keys
 *  
 * @author jstormes
 *
 */
class PHPSlickGrid_JSON_GapDataCache extends PHPSlickGrid_JSON_Abstract {

	private function BuildJoinedSelect()  {
				
		// Connect to tables
		$link_table 	= new Application_Model_Grids_GridLink();
		$right_table 	= new Application_Model_Grids_GridRight();
		$left_table 	= new Application_Model_Grids_GridLeft();
			
		// Get schema of tables
		$link_info 	= $link_table->info();
		$right_info = $right_table->info();
		$left_info 	= $left_table->info();
		
		// get primary keys of tables
		$link_primary 	= array_shift($link_info['primary']);
		$right_primary	= array_shift($right_info['primary']);
		$left_primary 	= array_shift($left_info['primary']);
			
		// Make column aliases - "(table name).(column name) as (table name)$(column name)"
		$column = array();
		foreach($link_info['cols'] as $key=>$value) {
			$columns[$link_info['name']."$".$value]=$link_info['name'].".".$value;
		}
		
		foreach($right_info['cols'] as $key=>$value) {
			$columns[$right_info['name']."$".$value]=$right_info['name'].".".$value;
		}
		
		foreach($left_info['cols'] as $key=>$value) {
			$columns[$left_info['name']."$".$value]=$left_info['name'].".".$value;
		}
			
		/*
		 * Select Left side records
		*
		* select * from grid_left
		* left join grid_link on grid_link.grid_left_id = grid_left.grid_left_id
		* left join grid_right on grid_link.grid_right_id = grid_right.grid_right_id
		*/
		$select_left = $left_table->select();
		$select_left->setIntegrityCheck(false);
		$select_left->from(array($left_info['name'] => $left_info['name']),$columns);
		$select_left->joinLeftUsing($link_info['name'], $left_primary, array());
		$select_left->joinLeft(array($right_info['name'] => $right_info['name']),
				$link_info['name'].".{$right_primary} = ".$right_info['name'].".{$right_primary}"
				, array());
			
		/*
		 * Select Right side records
		*
		* select * from grid_right
		* left join grid_link on grid_link.grid_right_id = grid_right.grid_right_id
		* left join grid_left on grid_link.grid_left_id = grid_left.grid_left_id
		*/
		$select_right = $right_table->select();
		$select_right->setIntegrityCheck(false);
		$select_right->from(array($right_info['name'] => $right_info['name']),$columns);
		$select_right->joinLeftUsing($link_info['name'], $right_primary, array());
		$select_right->joinLeft(array($left_info['name']=>$left_info['name']),
				$link_info['name'].".{$left_primary} = ".$left_info['name'].".{$left_primary}"
				, array());
			
		/*
		 * Union the two selects
		*/
		$union_select = $this->Table->select()->union(array($select_left,$select_right));
		$union_select->setIntegrityCheck(false);
		
		return $union_select;
		
	}
	
	public function getLength($options) {

		try
		{
			// Merge javascript options with php parameters.
			$parameters=array_merge_recursive($options,$this->parameters);
			
			$select = $this->BuildJoinedSelect($options);
			
			$count_select = $this->Table->select();
			$count_select->setIntegrityCheck(false);
			$count_select->from(new Zend_Db_Expr("(".$select.")"), 'COUNT(*) as num');
		
			/*
			 * Return the count of records
			 */
			$Res = $this->Table->fetchRow($count_select);
			return $Res->num;
		}
		catch (Exception $ex) { // push the exception code into JSON range.
			throw new Exception($ex, 32001);
		}
		
	}
	
	public function getBlock($block,$options) {
		
		try
		{
			
			// Merge javascript options with php parameters.
			$parameters=array_merge_recursive($options,$this->parameters);
			
			$select = $this->BuildJoinedSelect($options);
			$select->limit($options['blockSize'],$block*$options['blockSize']);
			
			// Build our order by
			foreach($parameters['order_list'] as $orderby) {
				$select->order($orderby);
			}
			
			/*
			 * Explode the results into row[Index][Table Name][Column] format
			*/
			$Results = $this->Table->fetchAll($select)->toArray();
				
			$ret = array();
			foreach($Results as $idx=>$Row) {
				foreach($Row as $key=>$value) {
					$t = explode("$", $key);
					$table = $t[0];
					$column = $t[1];
					$ret[$idx][$table][$column]=$value;
				}
			}
				
			return ($ret);
		}
		catch (Exception $ex) { // push the exception code into JSON range.
			throw new Exception($ex, 32001);
		}

	}
	
	/**
	 * update an existing row
	 *
	 * @param  array $row
	 * @param  array $options
	 * @return null
	 */
	public function updateItem($updt_dtm, $row, $options=null) {
		//sleep(5); // Simulate a slow reply
		try {
			//throw new Exception(print_r($this->PrimaryKey,true));
			$parameters=array_merge_recursive($options,$this->parameters);
	
			$Row=$this->Table->find($row[$this->PrimaryKey])->current();
			foreach($row as $Key=>$Value) {
				if (isset($Row[$Key])) {
					if ($Value=='null') $Value=null;
					$Row[$Key]=$Value;
				}
			}
			$Row[$this->UpdatedColumn]=null;
			$Row->save();
	
			return $this->getUpdated($updt_dtm,$options);
		}
		catch (Exception $ex) {
			throw new Exception(print_r($ex,true), 32001);
		}
	
	}
	
	/**
	 * add a new row
	 *
	 * @param  array $row
	 * @param  array $options
	 * @return null
	 */
	public function addItem($row,$options=null) {
		try {
			//throw new Exception(print_r($this->PrimaryKey,true));
			//             $parameters=array_merge($options,$this->parameters);
	
			//             $this->log->debug($this->parameters);
	
			$NewRow=$this->Table->createRow();
	
			foreach($this->Config->staticFields as $field) {
				$NewRow[$field['field']]=$field['value'];
				$this->log->debug($field);
			}
	
			foreach($row as $Key=>$Value) {
				if (isset($NewRow[$Key])) {
					if ($Value=='null') $Value=null;
					$NewRow[$Key]=$Value;
				}
			}
	
	
			$NewRow[$this->UpdatedColumn]=null;
			$NewRow->save();
	
			return null;
		}
		catch (Exception $ex) {
			throw new Exception(print_r($ex,true), 32001);
		}
	
	}
	
}