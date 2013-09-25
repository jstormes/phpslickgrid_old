<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        $this->config       	= Zend_Registry::get('config');
        $this->role_nm 			= Zend_Registry::get('role_nm');
        $this->user 			= Zend_Registry::get('user');
        
        // Grab a refrence to the logger.
        $this->log              = Zend_Registry::get('log');
        // Firebug Console Log example
        //$this->log->debug("this is a debug msg");
        
        $this->project_id=$this->_request->getParam('project_id',null);
    }

    public function indexAction()
    {
        // action body
        
    	// Add CSS
    	$this->view->headLink()->appendStylesheet('/css/datatable.css');
    	$this->view->headScript()->appendFile('/js/jquery.dataTables.js');
    	
    	// ********************************************
    	// Add the role to the view so it can show
    	// administrator controles.
    	// ********************************************
    	$this->view->role = $this->role_nm;
    	
    	// ********************************************
    	// Link to project, project_user and user tables
    	// ********************************************
    	$ProjectTable = new Application_Model_DbTable_Project();
    	$ProjectUserTable = new Application_Model_DbTable_ProjectUser();
    	$UserTable = new Application_Model_Shared_User();
    	
    	
    	/*******************************************************************
    	 * Get Users in Key value pairs
    	 *******************************************************************/
    	$this->view->users=$UserTable->getAdapter()->fetchAssoc("select * from user");
    	
    	// ********************************************
    	// Build Array for Table View
    	// ********************************************
    	$query = $ProjectTable->select();
		$query->where("(deleted = 0 or deleted is null) and (archived = 0)");
    	$this->view->projects = $ProjectTable->fetchAll($query)->toArray();
    	foreach($this->view->projects as $key => $row) {
    		$this->view->projects[$key]['project_lead'] = $UserTable->getUserByID($row['project_lead'])->user_nm;
    		$this->view->projects[$key]['team_members_assigned'] = $ProjectUserTable->fetchAll("project_id = ".$this->view->projects[$key]['project_id']);
    	}
    }
    
    public function archivedAction()
    {
    	// action body
    
    	// Add CSS
    	$this->view->headLink()->appendStylesheet('/css/datatable.css');
    	$this->view->headScript()->appendFile('/js/jquery.dataTables.js');
    	 
    	// ********************************************
    	// Add the role to the view so it can show
    	// administrator controles.
    	// ********************************************
    	$this->view->role = $this->role_nm;
    	 
    	// ********************************************
    	// Link to project, project_user and user tables
    	// ********************************************
    	$ProjectTable = new Application_Model_DbTable_Project();
    	$ProjectUserTable = new Application_Model_DbTable_ProjectUser();
    	$UserTable = new Application_Model_Shared_User();
    	 
    	 
    	/*******************************************************************
    	 * Get Users in Key value pairs
    	*******************************************************************/
    	$this->view->users=$UserTable->getAdapter()->fetchAssoc("select * from user");
    	 
    	// ********************************************
    	// Build Array for Table View
    	// ********************************************
    	$query = $ProjectTable->select();
		$query->where("(deleted = 0 or deleted is null) and (archived = 1)");
    	$this->view->projects = $ProjectTable->fetchAll($query)->toArray();
    	foreach($this->view->projects as $key => $row) {
    		$this->view->projects[$key]['project_lead'] = $UserTable->getUserByID($row['project_lead'])->user_nm;
    		$this->view->projects[$key]['team_members_assigned'] = $ProjectUserTable->fetchAll("project_id = ".$this->view->projects[$key]['project_id']);
    	}
    }
    

    public function editAction()
    {
    	// ********************************************
    	// Add CSS & Javascript to page header
    	// ********************************************
		/* Add Rich Text Editor */
    	$this->view->headScript()->appendFile('/ckeditor/ckeditor.js');
    	/* Add Multiselect Bootstrap plugin */
    	$this->view->headLink()->appendStylesheet('/multiselect/css/bootstrap-multiselect.css');
    	$this->view->headLink()->appendStylesheet('/multiselect/css/prettify.css');
    	$this->view->headScript()->appendFile('/multiselect/js/bootstrap-multiselect.js');
    	$this->view->headScript()->appendFile('/multiselect/js/prettify.js');
    
    	// ********************************************
    	// Get our list of possible team members
    	// ********************************************
		$user_model = new Application_Model_Shared_User();
		$this->view->users=$user_model->getUsersKeyValBy_app_id($this->config->app_id);
		//array_unshift($this->view->users, "None selected");
		
		// ********************************************
		// Link to Project Table
		// ********************************************
		$ProjectTable           = new Application_Model_DbTable_Project();
		$ProjectUserTable 		= new Application_Model_DbTable_ProjectUser();
		
		$this->team_members = array();
		if ($this->project_id===null) {
			$this->ProjectTableRow = $ProjectTable->createRow();
		}
		else {
			$this->ProjectTableRow = $ProjectTable->find($this->project_id)->current();
			$TeamMembers = $ProjectUserTable->fetchAll("project_id = ".(int)$this->project_id);
			foreach($TeamMembers as $member) {
				$this->team_members[]=$member->user_id;
			}
		}
		
    	
		// ********************************************
		// Set our default messages and style
		// ********************************************
		$this->view->page_title = $this->project_id?"Edit Project":"Create New Project";
		$this->view->save_btn_title = $this->project_id?"Update Project":"Create Project";
		
		$this->view->project_txt_help  = "Project name can contain any letters or numbers, with spaces.";
		$this->view->project_txt_style = ""; 
		
		
		// ********************************************
    	// Revcover our input values
		// ********************************************
    	$this->ProjectTableRow->project_txt = $this->view->project_txt  
    		= $this->_getParam('project_txt',$this->ProjectTableRow->project_txt);
    	 
    	$this->ProjectTableRow->project_lead = $this->view->project_lead 
    		= $this->_getParam('project_lead',$this->ProjectTableRow->project_lead);
    	
    	$this->team_members = $this->view->team_members 
    		= $this->_getParam('team_members',$this->team_members);
    	
    	$this->ProjectTableRow->project_desc = $this->view->project_desc 
    		= $this->_getParam('project_desc',$this->ProjectTableRow->project_desc);
    	
    	
    	// if postback
    	if ($this->getRequest()->isPost()) {
    		if (isset($_POST['cancel'])) {
    			$this->_redirect('/index');
    		}
    		
    		/* Track error state */
    		$error=false;
    		
    		// ************************************************
    		// Validate fields
    		// ************************************************
    		if (strlen($this->view->project_txt)<1) {
    			$this->view->project_txt_help  = "Project name is too short.";
    			$this->view->project_txt_style = "has-error";
    			$error=true;
    		}
    			
    		/* if no error then create project */
    		if (!$error) {

    			// ********************************************
    			// SAVE our project record
    			// ********************************************
    			$this->project_id=$this->ProjectTableRow->save();
    			
    			// ********************************************
    			// DELETE any team members from the project
    			// ********************************************
    			$ProjectUserTable->delete("project_id = ".(int)$this->project_id);
    			
    			// ********************************************
    			// CREATE New Records in project_user Table
    			// ********************************************
    			if(is_array($this->team_members)) {
    			
    				foreach($this->team_members as $team_member_user_id) {
    					if ($team_member_user_id!=0) {  /* Ignore "Non Selected */
	    					$NewRow                 = $ProjectUserTable->createRow();
	    					$NewRow->project_id     = $this->project_id;
	    					$NewRow->user_id        = $team_member_user_id;
	    					$project_usr_id 		= $NewRow->save();
    					}
    				}
    			
    			}
    			
    			// ********************************************
    			// REDIRECT to index view
    			// ********************************************
    			$this->_redirect('/index');
    		}
    	}
    
    }
    
    public function deleteAction()
    {
    	// ********************************************
    	// DISABLE Layout and Views
    	// ********************************************
    	$this->_helper->layout()->disableLayout(true);
    	$this->_helper->viewRenderer->setNoRender(true);
    
    	// ********************************************
    	// Link to project Table
    	// ********************************************
    	$projectmodel = new Application_Model_DbTable_Project();
    
    	// ********************************************
    	// UPDATE Delete Field (1 = Delete/Disable)
    	// ********************************************
    	$data = array(
    			'deleted'       => 1,
    			'updt_usr_id'	=> $this->user['user_id']
    	);
    
    	$where = $projectmodel->getAdapter()->quoteInto('project_id = ?', (int) $this->project_id);
    	$projectmodel->update($data, $where);
    
    
    	// ********************************************
    	// REDIRECT to index view
    	// ********************************************
    	$this->_redirect('/index');
    }
    
    public function archiveAction()
    {
    	// ********************************************
    	// DISABLE Layout and Views
    	// ********************************************
    	$this->_helper->layout()->disableLayout(true);
    	$this->_helper->viewRenderer->setNoRender(true);
    
    	// ********************************************
    	// Link to project Table
    	// ********************************************
    	$projectmodel = new Application_Model_DbTable_Project();
    
    	// ********************************************
    	// UPDATE archived Field (1 = Delete/Disable)
    	// ********************************************
    	$data = array(
    			'archived'       => 1,
    			'updt_usr_id'	=> $this->user['user_id']
    	);
    
    	$where = $projectmodel->getAdapter()->quoteInto('project_id = ?', (int) $this->project_id);
    	$projectmodel->update($data, $where);
    
    
    	// ********************************************
    	// REDIRECT to index view
    	// ********************************************
    	$this->_redirect('/index');
    }
    
    public function unarchiveAction()
    {
    	// ********************************************
    	// DISABLE Layout and Views
    	// ********************************************
    	$this->_helper->layout()->disableLayout(true);
    	$this->_helper->viewRenderer->setNoRender(true);
    
    	// ********************************************
    	// Link to project Table
    	// ********************************************
    	$projectmodel = new Application_Model_DbTable_Project();
    
    	// ********************************************
    	// UPDATE archived Field (1 = Delete/Disable)
    	// ********************************************
    	$data = array(
    			'archived'       => 0,
    			'updt_usr_id'	=> $this->user['user_id']
    	);
    
    	$where = $projectmodel->getAdapter()->quoteInto('project_id = ?', (int) $this->project_id);
    	$projectmodel->update($data, $where);
    
    
    	// ********************************************
    	// REDIRECT to index view
    	// ********************************************
    	$this->_redirect('/index');
    }
    

}

