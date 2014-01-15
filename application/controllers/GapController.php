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
        $this->GridConfiguration->DataModel 	= new Application_Model_Grids_GridLink();
        $this->GridConfiguration->project_id	= $this->project_id;
        $this->GridConfiguration->table_name	= 'grid_link';
        //$this->GridConfiguration->join = array();
        $this->GridConfiguration->JoinTo( new Application_Model_Grids_GridLeft() );
        $this->GridConfiguration->JoinTo( new Application_Model_Grids_GridRight() );
       // $t=new Application_Model_Grids_GridLeft();
       // $t->
        // This sets up our AJAX calls to use the rpcAction below.
        $this->GridConfiguration->jsonrpc              = $this->view->url(array('action'=>'rpc'));  // Our RPC URL is what we were passed replaing our action with rpc.
        // Set our project_id as a hard coded filter.
        $this->GridConfiguration->conditions[]         = new PHPSlickGrid_JSON_Condition('project_id',$this->project_id);
        
        
        
        
        $this->GridConfigurationLeft = new PHPSlickGrid_GridConfig();
        $this->GridConfigurationLeft->DataModel 	= new Application_Model_Grids_GridLeft();
        $this->GridConfigurationLeft->project_id	= $this->project_id;
        $this->GridConfigurationLeft->table_name	= 'grid_left';
        //$this->GridConfiguration->join = array();
        //$this->GridConfiguration->JoinTo( new Application_Model_Grids_GridLeft() );
        //$this->GridConfiguration->JoinTo( new Application_Model_Grids_GridRight() );
         //$t=new Application_Model_Grids_GridLeft();
        //$s= $t->select()->union()
        //$t->
        // This sets up our AJAX calls to use the rpcAction below.
        $this->GridConfigurationLeft->jsonrpc              = $this->view->url(array('action'=>'rpc'));  // Our RPC URL is what we were passed replaing our action with rpc.
        // Set our project_id as a hard coded filter.
        $this->GridConfigurationLeft->conditions[]         = new PHPSlickGrid_JSON_Condition('project_id',$this->project_id);

        
        
        $this->GridConfigurationRight = new PHPSlickGrid_GridConfig();
        $this->GridConfigurationRight->DataModel 	= new Application_Model_Grids_GridRight();
        $this->GridConfigurationRight->project_id	= $this->project_id;
        $this->GridConfigurationRight->table_name	= 'grid_right';
        //$this->GridConfiguration->join = array();
        //$this->GridConfiguration->JoinTo( new Application_Model_Grids_GridLeft() );
        //$this->GridConfiguration->JoinTo( new Application_Model_Grids_GridRight() );
        // $t=new Application_Model_Grids_GridLeft();
        // $t->
        // This sets up our AJAX calls to use the rpcAction below.
        $this->GridConfigurationRight->jsonrpc              = $this->view->url(array('action'=>'rpc'));  // Our RPC URL is what we were passed replaing our action with rpc.
        // Set our project_id as a hard coded filter.
        $this->GridConfigurationRight->conditions[]         = new PHPSlickGrid_JSON_Condition('project_id',$this->project_id);
        
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
    	
    	
    	/************** Setup Left Grid *********************/
    	// Pass the Grid Configuration to the view.
    	$this->view->GridConfigurationLeft = $this->GridConfigurationLeft;
    	 
    	// Get our column configuration directly from the source table
    	$GridColumnConfigurationLeft = new PHPSlickGrid_ColumnConfig($this->GridConfigurationLeft->DataModel);
    	 
    	// Hid any columns we don't want the user to see
    	$GridColumnConfigurationLeft->Hidden = array('project_id','updt_dtm');
    	 
    	// Set any columns we don't want the user to update
    	$GridColumnConfigurationLeft->ReadOnly = array('');
    	 
    	// Rename ColumnOptions - controls the Options for the individual columns.
    	$this->view->GridColumnConfigurationLeft = $GridColumnConfigurationLeft;
    	/*************** End Setup Left grid ******************/
    	
    	
    	/************** Setup Right Grid *********************/
    	// Pass the Grid Configuration to the view.
    	$this->view->GridConfigurationRight = $this->GridConfigurationRight;
    	
    	// Get our column configuration directly from the source table
    	$GridColumnConfigurationRight = new PHPSlickGrid_ColumnConfig($this->GridConfigurationRight->DataModel);
    	
    	// Hid any columns we don't want the user to see
    	$GridColumnConfigurationRight->Hidden = array('project_id','updt_dtm');
    	
    	// Set any columns we don't want the user to update
    	$GridColumnConfigurationRight->ReadOnly = array('');
    	
    	// Rename ColumnOptions - controls the Options for the individual columns.
    	$this->view->GridColumnConfigurationRight = $GridColumnConfigurationRight;
    	/*************** End Setup Left grid ******************/
    	
    	
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

