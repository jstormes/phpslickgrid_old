<?php

class IndexController extends Zend_Controller_Action
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
    	$query->where("(deleted = 0 or deleted is null)");
    	$this->view->projects = $ProjectTable->fetchAll($query)->toArray();
    	foreach($this->view->projects as $key => $row) {
    		$this->view->projects[$key]['project_lead'] = $UserTable->getUserByID($row['project_lead'])->user_nm;
    		$this->view->projects[$key]['team_members_assigned'] = $ProjectUserTable->fetchAll("project_id = ".$this->view->projects[$key]['project_id']);
    	}
    }

    public function newAction()
    {
    	// ********************************************
    	// Add CSS & Javascript to page header
    	// ********************************************
    	//$this->view->headLink()->appendStylesheet('/js/dropdown-check-list/doc/smoothness-1.8.13/jquery-ui-1.8.13.custom.css');
    	//$this->view->headLink()->appendStylesheet('/js/dropdown-check-list/css/ui.dropdownchecklist.css');
    	//$this->view->headScript()->appendFile('/js/dropdown-check-list/src/jquery-1.6.1.min.js');
    	//$this->view->headScript()->appendFile('/js/dropdown-check-list/src/jquery-ui-1.8.13.custom.min.js');
    	//$this->view->headScript()->appendFile('/js/dropdown-check-list/js/ui.dropdownchecklist-1.4-min.js');
    	/* CK Editor */
    	//$this->view->headLink()->appendStylesheet('/ckeditor/skins/BootstrapCK-Skin/editor.css');
    	$this->view->headScript()->appendFile('/ckeditor/ckeditor.js');
    	
    	$this->view->headLink()->appendStylesheet('/multiselect/css/bootstrap-multiselect.css');
    	$this->view->headLink()->appendStylesheet('/multiselect/css/prettify.css');
    	
    	$this->view->headScript()->appendFile('/multiselect/js/bootstrap-multiselect.js');
    	$this->view->headScript()->appendFile('/multiselect/js/prettify.js');
    
    	// ********************************************
    	// Option Menu Team Members
    	// ********************************************
    	//$ApplicationTable = new Application_Model_Shared_App();
    	//$this->view->team_members = $ApplicationTable->getUsersByApplicationAndClient($this->config->application_id,$this->current_customer['customer_id']);
    	$user_app_role_model = new Application_Model_Shared_UserAppRole();
		$user_model = new Application_Model_Shared_User();
    	
    	$row=$user_app_role_model->fetchRow($user_app_role_model->select()
    			->where('app_id = ?',$this->config->app_id));
    	if ($row) {
    		$this->view->team_members=$row->findDependentRowset($user_model);
    	}
    
    }
    
    public function createAction()
    {   
    	// ********************************************
    	// Link to Project Table
    	// ********************************************
    	$ProjectTable           = new Application_Model_DbTable_Project();
    
    	// ********************************************
    	// CREATE New Record in project Table
    	// ********************************************
    	$NewRow                 = $ProjectTable->createRow();
    	$NewRow->project_txt    = $this->_request->getParam('project_txt',0);
    	$NewRow->project_desc   = $this->_request->getParam('project_desc',0);
    	$NewRow->project_lead   = $this->_request->getParam('project_lead',0);
    	$project_id             = $NewRow->save();
    
    	// ********************************************
    	// Link to project_user Table
    	// ********************************************
    	$ProjectUserTable           = new Application_Model_DbTable_ProjectUser();
    
    	// ********************************************
    	// CREATE New Record in project_user Table
    	// ********************************************
    	if(is_array($this->_request->getParam('team_members'))) {
    
    		foreach($this->_request->getParam('team_members') as $team_member_user_id) {
    			$NewRow                 = $ProjectUserTable->createRow();
    			$NewRow->project_id     = $project_id;
    			$NewRow->uid            = $team_member_user_id;
    			$project_usr_id 		= $NewRow->save();
    		}
    
    	}
    
    	// ********************************************
    	// REDIRECT to index view
    	// ********************************************
    	$this->_redirect('/index');
    
    }
    
    public function editAction()
    {
    	// ********************************************
    	// Add CSS & Javascript to page header
    	// ********************************************
    	$this->view->headLink()->appendStylesheet('/js/dropdown-check-list/doc/smoothness-1.8.13/jquery-ui-1.8.13.custom.css');
    	$this->view->headLink()->appendStylesheet('/js/dropdown-check-list/css/ui.dropdownchecklist.css');
    	$this->view->headScript()->appendFile('/js/dropdown-check-list/src/jquery-1.6.1.min.js');
    	$this->view->headScript()->appendFile('/js/dropdown-check-list/src/jquery-ui-1.8.13.custom.min.js');
    	$this->view->headScript()->appendFile('/js/dropdown-check-list/js/ui.dropdownchecklist-1.4-min.js');
    
    	// ********************************************
    	// Link to project & user Table
    	// ********************************************
    	$ProjectTable = new Application_Model_DbTable_Project();
    	$UserTable = new Application_Model_Common_User();
    
    	// ********************************************
    	// FETCHALL Projects
    	// ********************************************
    	$query = $ProjectTable->select();
    	$query->where("project_id = ? and (deleted = 0 or deleted is null)",$this->_request->getParam('project_id',0));
    	$this->view->projects = $ProjectTable->fetchAll($query);
    
    	// ********************************************
    	// Link to user table (in Common/Applicaton model)
    	// ********************************************
    	$ApplicationTable = new Application_Model_Common_Application();
    	$this->view->team_members = $ApplicationTable->getUsersByApplicationAndClient($this->config->application_id,$this->current_customer['customer_id']);
    	$this->view->project_id = $this->_request->getParam('project_id');
    
    	// ********************************************
    	// Link to project_user Table
    	// ********************************************
    	$ProjectUserTable           = new Application_Model_DbTable_ProjectUser();
    
    	// ********************************************
    	// FETCHALL Team Members Assiged to Project
    	// ********************************************
    	$this->view->team_members_assigned = $ProjectUserTable->getSelectedTeamMembersByProjectId($this->_request->getParam('project_id',0));
    
    }
    
    public function updateAction()
    {
    
    	// ********************************************
    	// DISABLE Layout and Views
    	// ********************************************
    	$this->_helper->layout()->disableLayout(true);
    	$this->_helper->viewRenderer->setNoRender(true);
    
    	// ********************************************
    	// Link to project Table
    	// ********************************************
    	$ProjectTable = new Application_Model_DbTable_Project();
    
    	// REQUEST & SET project_id
    	$project_id = $this->_request->getParam('project_id');
    
    	// ********************************************
    	// UPDATE Record with data array
    	// ********************************************
    	$data = array(
    			'project_txt'           => (!$this->_request->getParam('project_txt') ? NULL : $this->_request->getParam('project_txt')),
    			'project_desc'          => (!$this->_request->getParam('project_desc') ? NULL : $this->_request->getParam('project_desc')),
    			'project_lead'          => (!$this->_request->getParam('project_lead') ? NULL : $this->_request->getParam('project_lead')),
    			'updt_usr_id'           => $this->user['user_id'],
    			'updt_dtm'              => new Zend_Db_Expr('NOW()')
    	);
    
    	$where = $ProjectTable->getAdapter()->quoteInto('project_id = ?', (int) $project_id);
    	$ProjectTable->update($data, $where);
    
    	// ********************************************
    	// Link to project_user Table
    	// ********************************************
    	$ProjectUserTable = new Application_Model_DbTable_ProjectUser();
    
    	// ********************************************
    	// DELETE all records associated with project_id in project_user Table
    	// ********************************************
    	$where = $ProjectUserTable->getAdapter()->quoteInto('project_id = ?', (int) $project_id);
    	$ProjectUserTable->delete($where);
    
    	// ********************************************
    	// INSERT NEW records associated with project_id in project_user Table
    	// ********************************************
    	if(is_array($this->_request->getParam('s1'))) {
    
    		foreach($this->_request->getParam('s1') as $s1_user_id) {
    			$NewRow                 = $ProjectUserTable->createRow();
    			$NewRow->project_id     = $project_id;
    			$NewRow->uid            = $s1_user_id;
    			$NewRow->crea_usr_id    = $this->user['user_id'];
    			$NewRow->crea_dtm       = new Zend_Db_Expr('NOW()');
    			$NewRow->updt_usr_id    = $this->user['user_id'];
    			$NewRow->updt_dtm       = new Zend_Db_Expr('NOW()');
    			$project_usr_id         = $NewRow->save();
    		}
    
    	}
    
    	// ********************************************
    	// REDIRECT to index view
    	// ********************************************
    	$this->_redirect('/index');
    
    
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
    			'updt_usr_id'   => $this->user['user_id'],
    			'updt_dtm'      => new Zend_Db_Expr('NOW()')
    	);
    
    	// var_dump($this->_request->getParam('project_id'));
    
    	$where = $projectmodel->getAdapter()->quoteInto('project_id = ?', (int) $this->_request->getParam('project_id'));
    	$projectmodel->update($data, $where);
    
    	// ********************************************
    	// Link to project_user Table
    	// ********************************************
    	$ProjectUserTable   = new Application_Model_DbTable_ProjectUser();
    
    	// ********************************************
    	// DELETE all records in project_user Table associated with project_id
    	// ********************************************
    	$where = $ProjectUserTable->getAdapter()->quoteInto('project_id = ?', $this->_request->getParam('project_id'));
    	$ProjectUserTable->delete($where);
    
    	// ********************************************
    	// REDIRECT to index view
    	// ********************************************
    	$this->_redirect('/index');
    }
    

}

