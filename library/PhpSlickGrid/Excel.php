<?php
class PhpSlickGrid_Excel 
{
	public $file;
	
	/** @var PHPExcel */
	public $objPHPExcel;
	
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
    	$this->objPHPExcel = PHPExcel_IOFactory::load($file);
    }
    
    /**
     * return a list of tabs from the excel file
     *
     * By: jstormes Oct 9, 2013
     *
     * @return multitype:
     */
    public function get_tables() {
    	
    	$sheets = $this->objPHPExcel->getSheetNames();
    	//$this->log->debug($sheets);
    	
    	return $sheets;
    }
}