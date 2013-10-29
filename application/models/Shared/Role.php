<?php

class Application_Model_Shared_Role extends Zend_Db_Table_Abstract
{

    protected $_name = 'role';
    
    /**
     * Use the PhpSlickGrid Rowset class
     * rather than the Zend Rowset class.
     * 
     * @var string
     */
    protected $_rowsetClass = 'PHPSlickGrid_Db_Table_Rowset';
    
    protected $_referenceMap    = array(
    		'user_app_role' => array(
    				'columns'           => array('role_id'),
    				'refTableClass'     => 'Application_Model_Shared_UserAppRole',
    				'refColumns'        => array('role_id')
    				)

    );
    
    protected function _setupDatabaseAdapter()
    {	
        // see _initDatabase() in the Bootstrap.php file
        $this->_db = Zend_Registry::get('shared_db');
        parent::_setupDatabaseAdapter();
    }
    

}

