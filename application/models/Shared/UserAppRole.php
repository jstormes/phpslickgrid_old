<?php

class Application_Model_Shared_UserAppRole extends Zend_Db_Table_Abstract
{

    protected $_name = 'user_app_role';
    
    /**
     * Use the PhpSlickGrid Rowset class
     * rather than the Zend Rowset class.
     * 
     * @var string
     */
    protected $_rowsetClass = 'PhpSlickGrid_Db_Table_Rowset';
    
    protected function _setupDatabaseAdapter()
    {
        // see _initDatabase() in the Bootstrap.php file
        $this->_db = Zend_Registry::get('shared_db');
        parent::_setupDatabaseAdapter();
    }
    

    public function getUserAppRoleByUserIDandAppID($user_id, $app_id) {
    	$sel = $this->select();
    	
    	$sel->from(array('u' => 'user_app_role'),
    			array('r.role_nm'));
    	$sel->join(array('r' => 'role'),
    			'r.role_id = u.role_id');
    	
    	$sel->where("user_id = ? ",$user_id);
    	$sel->where("app_id = ? ",$app_id);
    	$UserRow=$this->fetchAll($sel)->current();
    	if ($UserRow) {
    		if ($UserRow->deleted==false){
    			return $UserRow;
    		}
    	}
    	return false;
    }
}

