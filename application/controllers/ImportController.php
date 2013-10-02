<?php

class ImportController extends Zend_Controller_Action
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
    }

    public function indexAction()
    {
        // action body
        
    }
    
    /* Step1 reads the xlsx file and sets up mapping */
    public function step1Action() {
    	$this->tmpfile=$this->_request->getParam('tmpfile',null);
    	$this->destination_name=$this->_request->getParam('destination',null);
    	
    }
    
    /* Step2 attempts to load the data into the 
     * destination table.
     */
    public function step2Action() {
    	
    }

}

