<?php
class PHPSlickGrid_Excel extends Zend_Db_Adapter_Abstract
{
	public $file;
	
	/** @var PHPExcel */
	public $objPHPExcel;
	
	/** @var bool */
	public $firstRowNames = 1;
	
	/**
	 * 
	 *
	 * By: jstormes Oct 9, 2013
	 *
	 * @param string $file
	 * @param unknown $options
	 */
	public function __construct($file=null, $options = array())
    {
    	$this->log        = Zend_Registry::get('log');
    	
    	$this->file=$file;
    	//$this->objPHPExcel = PHPExcel_IOFactory::load($file);
    	//$this->objPHPExcel->setReadDataOnly(true);
    	
    	
    	
    	$inputFileType = 'Excel2007';
    	
    	/**  Create a new Reader of the type defined in $inputFileType  **/
    	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    	/**  Advise the Reader that we only want to load cell data  **/
    	$objReader->setReadDataOnly(true);

    	/**  Load $inputFileName to a PHPExcel Object  **/
    	$this->objPHPExcel = $objReader->load($file);
    	 
    }
    
    public function ExcelFetchAllArray($tableName){
    	$this->objPHPExcel->setActiveSheetIndexByName($tableName);
    	if ($this->firstRowNames==0)
    		return $this->objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
    	
    	$data=$this->objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
    	$ret = array();
    	
    	$colum_map=array();
    	$Schema = $this->describeTable($tableName);
    	foreach($Schema as $RealColumnName=>$s) {
    		$column_map[$s['EXCEL_COLUMN']]=$RealColumnName;
    	}
    	
    	$idx=1;
    	foreach($data as $row=>$columns) {
    		if ($idx++!=1){
	    		$ret[$row]=array();
	    		foreach($columns as $ExcelColumName=>$Value) {
	    			$ret[$row][$column_map[$ExcelColumName]]=$Value;
	    		}
    		}
    	}
    	
    	return $ret;
    	
    }
    
    public function getCurrentSheet() {
    	$workSheet = $this->objPHPExcel->getActiveSheet();	
    	return $workSheet->getTitle();
    }
    
    public function getCurrentSheetIdx() {
    	return $this->objPHPExcel->getActiveSheetIndex();
    }
    
    /**
     * return a list of tabs from the excel file
     *
     * By: jstormes Oct 9, 2013
     *
     * @return multitype:
     */
    public function listTables() {
    	return $this->objPHPExcel->getSheetNames();
    }
    
    public function describeTable($tableName, $schemaName = null) {
    	$this->objPHPExcel->setActiveSheetIndexByName($tableName);
    	$worksheet = $this->objPHPExcel->getActiveSheet();
    	
    	//$this->log->debug($worksheet->get);
    	//return;
    	$sheet=$this->objPHPExcel->getActiveSheet();
    	$sheetData = $sheet->toArray(null,true,true,true);
    	//$this->log->debug($sheetData);
    	
    	$columns = array();
    	$i=1;
    	foreach($sheetData[1] as $key=>$value) {
    		$columns[$this->firstRowNames?$value:$key] = array(
    			'SCHEMA_NAME' => null,
    				'EXCEL_COLUMN' => $key,
    				'TABLE_NAME' => $tableName,
    				'COLUMN_NAME' => $this->firstRowNames?$value:$key,
    				'COLUMN_POSITION' => $i++,
    				'DATA_TYPE' => null,
    				'DEFAULT' => null,
    				'NULLABLE' => true,
    				'LENGTH' => 0,
    				'SCALE' => null,
    				'PRECISION' => null,
    				'UNSIGNED' => null,
    				'PRIMARY' => false,
    				'PRIMARY_POSITION' => false,
    				'IDENTITY' => false
    		);
    	}
    	
    	// Get maximum length and data types for columns
    	/*
    	 * A better strategy would be to escalate the type.  That is to say 
    	 * see if the data can fit into a small int, then bigint, then 
    	 * float, then decimal.  On the loader side allow smaller types to 
    	 * put into larger types without message.
    	 */
    	$i=0;
    	foreach($sheetData as $idx=>$row) {
//     		$i++;
     		if ($i++>1000) break;
    		if (($idx!=1)||($this->firstRowNames==0)) {
	    		foreach($columns as $column_name=>$column) {
	    			$value=$row[$column['EXCEL_COLUMN']];
	    			$type=$sheet
	    				->getCell($column['EXCEL_COLUMN'].$idx)
	    				->getDataType();
	    			
	    			// If type is numeric then check if type is datetime.
// 	    			if ($type=='n') {
// 	    				if (PHPExcel_Shared_Date::isDateTime(
// 		    				$this->objPHPExcel
// 		    				->getActiveSheet()
// 		    				->getCell($column['EXCEL_COLUMN'].$idx)
// 	    				))
// 	    					$type='datetime';
// 	    			}
	    				
	    			
	    			// set the types
	    			if ($value!==null) {
		    			if ($columns[$column_name]['DATA_TYPE']===null)
		    				$columns[$column_name]['DATA_TYPE']=$type;	
		    			
		    			if ($columns[$column_name]['DATA_TYPE']!=$type) 
		    				$columns[$column_name]['DATA_TYPE']='mixed';
		    			
		    			if ($columns[$column_name]['LENGTH']<strlen($value))
		    				$columns[$column_name]['LENGTH']=strlen($value);
	    			}
	    			
	    			//$this->log->debug(array('name'=>$column['EXCEL_COLUMN'],'value'=>$value,'type'=>$column['DATA_TYPE']));
	    		}
    		}
    	}

    	
    	//TODO: add integer types and numeric sizes!!!!!
    	// Convert Excel types to SQL types
    	foreach($columns as $column_name=>$column) {
    		
    		// Save our Excel type in case we need it later
    		$columns[$column_name]['EXCEL_TYPE']=$columns[$column_name]['DATA_TYPE'];
    		
    		switch ($columns[$column_name]['DATA_TYPE']) {
    			case 'mixed':
    				$columns[$column_name]['DATA_TYPE']='varchar';
    				break;
    			case 'str':
    			case 's':
    			case 'f':
    			case 'b':
    			case 'null':
    			case 'inlineStr':
    				$columns[$column_name]['DATA_TYPE']='varchar';
    				break;
    			case 'n':
    				$columns[$column_name]['DATA_TYPE']='decimal';
    				$columns[$column_name]['LENGTH']='';
    				break;
    		}
    	}
    	
	    return $columns;	
    }
    
    protected function _connect() {
    	
    }
    
    public function isConnected() {
    	
    }
    
    public function closeConnection() {
    	
    }
    
    public function prepare($sql) {
    	
    }
    
    public function lastInsertId($tableName = null, $primaryKey = null) {
    	
    }
    
    protected function _beginTransaction() {
    	
    }
    
    protected function _commit() {
    	
    }
    
    protected function _rollBack() {
    	
    }
    
    public function setFetchMode($mode) {
    	
    }
    
    public function limit($sql, $count, $offset = 0) {
    	
    }
    
    function supportsParameters($type) {
    	
    }
    
    public function getServerVersion() {
    	
    }
    
    /**
     * return a list of column names, if $firstRowName is true
     * scan the 
     *
     * By: jstormes Oct 23, 2013
     *
     */
    public function get_columns() {
    	
    }
}