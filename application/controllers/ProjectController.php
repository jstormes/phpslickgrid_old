<?php

class ProjectController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        $this->config       	= Zend_Registry::get('config');
        $this->role_nm 			= Zend_Registry::get('role_nm');
        
        // Grab a refrence to the logger.
        $this->log              = Zend_Registry::get('log');
        // Firebug Console Log example
        //$this->log->debug("this is a debug msg");
        
        $this->project_id=$this->_request->getParam('project_id',null);
        
        
        /* The layout is not part of the current view
         * you have to grab a copy of the layout to
        * change values in the header and footer;
        */
        $this->layout = Zend_Layout::getMvcInstance();
    }

    
    public function indexAction()
    {
        // action body   
    	$gridTable = new Application_Model_DbTable_Grid();
    	
    	$ImportFile = new PhpSlickGrid_FileManager_ExcelImport("TestUpload", $gridTable,
			array("HTML"=>"<i class='icon-upload icon-large'></i>",
    		"Help"=>"Select Excel file to upload."
    		));
    	$this->layout->footer_right=$ImportFile->Button();
    	
    	
    	
    	//$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    	
    	//$this->log->debug($dbAdapter->describeTable('grid'));
    	
//     	$this->layout->footer_right = $this->view->ModalUpload("TestModal"
//     			,'PhpSlickGrid_FileManager_PHPExcelLoader',
//     		array("HTML"=>"<i class='icon-upload icon-large'></i>",
//     		"Help"=>"Select Excel file to upload."
//     		));
    }
    
    public function importAction() 
    {
    	// action body
    	
    	
    }

}

