<?php

class Application_Model_Shared_App extends Zend_Db_Table_Abstract
{

    protected $_name = 'app';
    protected $_db = null;
    
    
    protected function _setupDatabaseAdapter()
    {
        // see _initDatabases() in the Bootstrap.php file
        $this->_db = Zend_Registry::get('shared_db');
        parent::_setupDatabaseAdapter();
    }
    
}

