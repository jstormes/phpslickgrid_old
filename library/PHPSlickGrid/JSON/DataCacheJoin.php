<?php
class PHPSlickGrid_JSON_DataCacheJoin extends PHPSlickGrid_JSON_Abstract {
	
	
	/**
	 * returns the number of data items in the set
	 *
	 * @param array options
	 * @return integer
	 */
	public function getLength($options) {
		try
		{
			// TODO: this line does not work.
			$parameters=array_merge($options,$this->parameters);
			//throw new Exception(print_r($options,true),32001);
			
			$sel = $this->Table->select();
			$sel->from(array($this->TableName),array('num'=>'COUNT(*)'));
			$this->addConditionsToSelect($this->TableName,$this->Config->conditions, $sel);
			$this->createWhere($this->TableName,$sel, $options['where_list']);
			$Res = $this->Table->fetchRow($sel);
			return $Res->num+10;
		}
		catch (Exception $ex) {
			throw new Exception($ex,32001);
		}
	}
	
	
	public function getBlock($block,$options) {
		
		try
		{
			// Connect to tables
			$link_table 	= new Application_Model_Grids_GridLink();
			$right_table 	= new Application_Model_Grids_GridRight();
			$left_table 	= new Application_Model_Grids_GridLeft();
			
			// Get scheam of tables
			$link_info 	= $link_table->info();
			$right_info = $right_table->info();
			$left_info 	= $left_table->info();
			
			// Get columns  Make each column alias; "(column name) as (table name)$(column name)" 
			$column = array();
			$link_columns 	= array();
			foreach($link_info['cols'] as $key=>$value) {
				$link_columns[$link_info['name']."$".$value]=$value;
				$columns[$link_info['name']."$".$value]=$link_info['name'].".".$value;
			}
			
			$right_columns 	= array();
			foreach($right_info['cols'] as $key=>$value) {
				$right_columns[$right_info['name']."$".$value]=$value;
				$columns[$right_info['name']."$".$value]=$right_info['name'].".".$value;
			}
			
			$left_columns 	= array();
			foreach($left_info['cols'] as $key=>$value) {
				$left_columns[$left_info['name']."$".$value]=$value;
				$columns[$left_info['name']."$".$value]=$left_info['name'].".".$value;
			}
			
			//$columns = array_merge($link_columns,$right_columns,$left_columns);
			
			
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
 			$select_left->joinLeftUsing($link_info['name'], 'grid_left_id', array());
 			$select_left->joinLeft(array($right_info['name'] => $right_info['name']), 
 					$link_info['name'].'.grid_right_id = '.$right_info['name'].".grid_right_id"
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
 			$select_right->joinLeftUsing($link_info['name'], 'grid_right_id', array());
 			$select_right->joinLeft(array($left_info['name']=>$left_info['name']),
 					 $link_info['name'].'.grid_left_id = '.$left_info['name'].".grid_left_id"
 					, array());
			
			/* 
			 * Union the two selects
			 */
			$union_select = $this->Table->select()->union(array($select_left,$select_right));
			$union_select->setIntegrityCheck(false);
		
			/* Explode the results into row[Table Name][Index][Column] format
			 * 
			 */
			$Results = $this->Table->fetchAll($union_select)->toArray();
			
			$ret = array();
			foreach($Results as $idx=>$Row) {
				foreach($Row as $key=>$value) {
					$t = explode("$", $key);				
					$table = $t[0];
					$column = $t[1];
					$ret[$table][$idx][$column]=$value;
				}
			}
			
			return ($ret);
		}
		catch (Exception $ex) { // push the exception code into JSON range.
			throw new Exception($ex, 32001);
		}
	
	}
	
	
	/**
	 * returns the item at a given index
	 *
	 * @param integer block
	 * @param array options
	 * @return array
	 */
	public function getBlock2($block,$options) {
		//sleep(5); // Simulate a slow reply
		try
		{
			//$this->log->debug("getBlock");
			//throw new Exception(print_r($options,true));
			// Merge Options from both the controller and JSON call
			$parameters=array_merge_recursive($options,$this->parameters);
			//throw new Exception(print_r($parameters,true));
	
			//$this->Table->getDefaultAdapter()->setFetchMode(Zend_Db::FETCH_NUM);
			
			$sel = $this->Table->select();
			$sel->setIntegrityCheck(false);
			
						
			$columns = array();
			
			foreach($this->info['cols'] as $key=>$value) {
				$columns[$this->TableName."$".$value]=$value;
			}
			
			/*************** sel 1 ****************/
			$sel->from(array($this->TableName => $this->TableName),$columns);
			if (isset($this->Config->join)) {
 				foreach ($this->Config->join as $join_table) {
 					$join_grid = new $join_table(); 					
 					$info=$join_grid->info();
 					$JoinTableName = $info['name'];
 					
 					$PrimaryKey=array_shift($info['primary']);
 					$columns = array();
 					foreach($info['cols'] as $key=>$value) {
 						$columns[$JoinTableName."$".$value]=$value;
 					}
 					$sel->joinLeftUsing($JoinTableName, $PrimaryKey, $columns);
 					//$sel->joinRightUsing($JoinTableName, $PrimaryKey, $columns);
 				}
			}

			//$this->addConditionsToSelect($this->TableName,$this->Config->conditions, $sel);
			//$this->createWhere($this->TableName,$sel, $options['where_list']);
			//$sel->limit($options['blockSize'],$block*$options['blockSize']);
			/************* End sel 1 *****************/
			
			
			$sel2 = $this->Table->select();
			$sel2->setIntegrityCheck(false);
				
			$columns = array();
				
			foreach($this->info['cols'] as $key=>$value) {
				$columns[$this->TableName."$".$value]=$value;
			}
			
			/*************** sel 2 ****************/
			$sel2->from(array($this->TableName => $this->TableName),$columns);
			if (isset($this->Config->join)) {
				foreach ($this->Config->join as $join_table) {
					$join_grid = new $join_table();
					$info=$join_grid->info();
					$JoinTableName = $info['name'];
			
					$PrimaryKey=array_shift($info['primary']);
					$columns = array();
					foreach($info['cols'] as $key=>$value) {
						$columns[$JoinTableName."$".$value]=$value;
					}
					$sel2->joinRightUsing($JoinTableName, $PrimaryKey, $columns);
					//$sel->joinRightUsing($JoinTableName, $PrimaryKey, $columns);
				}
			}
			
			//$this->addConditionsToSelect($this->TableName,$this->Config->conditions, $sel2);
			//$this->createWhere($this->TableName,$sel2, $options['where_list']);
			//$sel->limit($options['blockSize'],$block*$options['blockSize']);
			/************* End sel 2 *****************/
			
			$this->log->debug($sel->__toString());
			$union_sel = $this->Table->select()->union(array($sel,$sel2));
			$union_sel->setIntegrityCheck(false);
			//$union_sel->limit($options['blockSize'],$block*$options['blockSize']);
			
			
			// Build our order by
			//foreach($parameters['order_list'] as $orderby) {
			//	$sel->order($orderby);
			//}
			

			$Results = $this->Table->fetchAll($union_sel)->toArray();
			
			$this->log->debug("Results");
			$this->log->debug($Results);
			
			
			$ret = array();
			//$ret[$table] = array();
				
			foreach($Results as $idx=>$Row)
				foreach($Row as $key=>$value) {
					//$this->log->debug($key);
					$t = explode("$", $key);
					
 					$table = $t[0];
 					$column = $t[1];
 					$ret[$table][$idx][$column]=$value;
				}
			
			$this->log->debug($ret);
			
 			if ($ret) {
// 				$ret = array();
// 				$ret[$this->TableName]=$Results->toArray();
 				return ($ret);
 			}
			return null;
		}
		catch (Exception $ex) {
			throw new Exception($ex, 32001);
		}
	}
	
	/**
	 * return the date time of the newest record and the newest record by ID
	 *
	 * @param array options
	 * @return array
	 */
	public function getNewest($options=null) {
		try {
			$parameters=array_merge($options,$this->parameters);
	
			$Results = array();
	
	
			// TODO: Where is add where to select????
			$sel=$this->Table->select();
			//$sel->from(array($this->TableName => $this->TableName),array('*'));
			$this->addConditionsToSelect($this->TableName, $this->Config->conditions, $sel);
			$sel->from($this->TableName, array(new Zend_Db_Expr("MAX(".$this->UpdatedColumn.") AS max_updt_dtm")));
			$row=$this->Table->fetchAll($sel);
			if ($row)
				$Results['max_updt_dtm']=$row->current()->toArray();
			else
				$Results['max_updt_dtm']=null;
	
			$sel=$this->Table->select();
			//$sel->from(array($this->TableName => $this->TableName),array('*'));
			$sel->from(array($this->TableName => $this->TableName), array(new Zend_Db_Expr("MAX(".$this->PrimaryKey.") AS max_id")));
			$row=$this->Table->fetchAll($sel);
			if ($row)
				$Results['max_id']=$row->current()->toArray();
			else
				$Results['max_id']=null;
	
			return $Results;
		}
		catch (Exception $ex) {
			throw new Exception($ex, 32001);
		}
	}
	
	/**
	 * return the primay keys of all rows that are newer than
	 * the passed date.
	 *
	 * NOTE: if date created=date updated then don't return that row.
	 * Keeps the system from jumping rows???????
	 *
	 * @param string updt_dtm
	 * @param array options
	 * @return array
	 */
	public function getUpdated($updt_dtm,$options=null) {
		//throw new Exception('Error Msg', 32001);
		//sleep(10);
		try {
			$parameters=array_merge_recursive($options,$this->parameters);
			$res = array();
			if (isset($updt_dtm)) {
				$sel = $this->Table->select();
				$sel->from(array($this->TableName => $this->TableName),array('*'));
				$this->addConditionsToSelect($this->TableName, $this->Config->conditions, $sel);
				$sel->where($this->UpdatedColumn.' > ?',$updt_dtm);
				$res=$this->Table->fetchAll($sel);
				if (count($res)!=0) {
					//$this->log->debug("found new data ".$this->UpdatedColumn);
					return $res->toArray();
				}
			}
			return $res;
		}
		catch (Exception $ex) {
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
	
	/**
	 * search and replace replace values
	 *
	 * @param  string $oleName
	 * @param  string $newName
	 * @param  string $column
	 * @param  array $options
	 * @return null
	 */
	public function replaceItems($oldValue, $newValue, $column, $where) {
		//sleep(5); // Simulate a slow reply
		try {
			//throw new Exception(print_r($this->PrimaryKey,true));
			//$parameters=array_merge_recursive($options,$this->parameters);
	
			//$this->log->debug("updated '".$oldValue."' to '".$newValue."'");
	
			if ($oldValue=='')
				$oldValue=null;
	
			$sel = $this->Table->select();
			$sel->from(array($this->TableName => $this->TableName),array('*'));
			$this->addConditionsToSelect($this->TableName, $this->Config->conditions, $sel);
			$this->createWhere($this->TableName, $sel, $where);
	
			$whereData = $sel->getPart( Zend_Db_Select::WHERE );
	
			if ($oldValue=='')
				$whereData[] = " AND ($column = '' or $column is null) ";
			else
				$whereData[] = " AND ($column = '$oldValue') ";
	
			$newWhere=implode(' ',$whereData);
	
			//$this->log->debug(print_r($newWhere,true));
			//return array();
	
			$db=$this->Table->getAdapter();
	
			$n = $db->update($this->TableName,array($column=>$newValue),$newWhere);
	
			//$this->log->debug("updated ".$n);
	
		}
		catch (Exception $ex) {
			if (strstr($ex->getMessage(),'foreign key constraint fails')) {
				$ex = "That value cannot be used in these cells.";
			}
			throw new Exception(print_r($ex,true), 32001);
		}
	
	}
	
}