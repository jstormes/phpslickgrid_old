<?php

class GapController extends Zend_Controller_Action
{

    public function init()
    {
    	/************* Begin Boiler Plate ***************/
   	
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
        
        /***************** End boiler plate **************/
        
        // Options will will use for the grid.
        $this->GridConfiguration = new PHPSlickGrid_GridConfig();
        $this->GridConfiguration->DataModel 	= new Application_Model_DbTable_Grid();
        $this->GridConfiguration->project_id	= $this->project_id;
        $this->GridConfiguration->table_name	= 'grid';
        // This sets up our AJAX calls to use the rpcAction below.
        $this->GridConfiguration->jsonrpc              = $this->view->url(array('action'=>'rpc'));  // Our RPC URL is what we were passed replaing our action with rpc.
        // Set our project_id as a hard coded filter.
        $this->GridConfiguration->conditions[]         = new PHPSlickGrid_JSON_Condition('project_id',$this->project_id);
        
    }

    
    public function indexAction()
    {
        // action body 


        	
    	/****************  Setup the Grid *******************/
    	// Pass the Grid Configuration to the view.
    	$this->view->GridConfiguration = $this->GridConfiguration;
    	
    	// Get our column configuration directly from the source table
    	$GridColumnConfiguration = new PHPSlickGrid_ColumnConfig($this->GridConfiguration->DataModel);
    	
    	// Hid any columns we don't want the user to see
    	$GridColumnConfiguration->Hidden = array('project_id','updt_dtm');
    	
    	// Set any columns we don't want the user to update
    	$GridColumnConfiguration->ReadOnly = array('');
    	
    	// Rename ColumnOptions - controls the Options for the individual columns.
    	$this->view->GridColumnConfiguration = $GridColumnConfiguration;
    	/*************  End Setup the Grid ******************/
    	
//     	$ImportFile = new ExcelMgr_View_ImportExcel("TestUpload", $this->GridConfiguration->DataModel, $this->project_id,
// 			array("HTML"=>"<i class='icon-upload icon-large'></i>",
//     		"Help"=>"Select Excel file to upload."
//     		));
//     	$this->layout->footer_right=$ImportFile->Button();

    	
    }
    
    /*********************************************************************
     * This is the AJAX entry point back into the server.
    *
    * The Grid Configuration property jsonrpc should point back to this
    * action.  Also, any url parameters such as project_id should also
    * be included in the url used to call the AJAX as the object in the
    * init() will probibly need them.
    ********************************************************************/
    public function rpcAction() {
    	// Disable menus and don't render any view.
    	$this->_helper->layout()->disableLayout(true);
    	$this->_helper->viewRenderer->setNoRender(true);

    	// Create a new instance of a JSON webservice service using our source table and grid configuration.
    	$server = new PHPSlickGrid_JSON($this->GridConfiguration->DataModel,$this->GridConfiguration);
    
    	// Expose the JSON database table service trough this action.
    	$server->handle();
    }
    

}

