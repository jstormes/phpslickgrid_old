<?php

class Application_Model_Shared_App extends Zend_Db_Table_Abstract
{

    protected $_name = 'app';
    
    /**
     * Use the PhpSlickGrid Rowset class
     * rather than the Zend Rowset class.
     * 
     * @var string
     */
    protected $_rowsetClass = 'PHPSlickGrid_Db_Table_Rowset';
    
    protected $_referenceMap    = array(
    		'user_app_role' => array(
    				'columns'           => array('app_id'),
    				'refTableClass'     => 'Application_Model_Shared_UserAppRole',
    				'refColumns'        => array('app_id')
    		)
    
    );
    
    protected function _setupDatabaseAdapter()
    {
        // see _initDatabases() in the Bootstrap.php file
        $this->_db = Zend_Registry::get('shared_db');
        parent::_setupDatabaseAdapter();
    }
    
}

