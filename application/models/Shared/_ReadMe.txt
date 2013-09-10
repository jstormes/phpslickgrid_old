This directory is for models that access the common database shared by multiple applications.  

In the applicaiton.ini file the connection information is specified by:

;-
;- Database connection for common (federated) data
;-
resources.multidb.common.adapter = PDO_MYSQL
resources.multidb.common.host = 127.0.0.1
resources.multidb.common.username = common
resources.multidb.common.dbname = common
resources.multidb.common.password = 
;-


In the Bootstrap.php file the connection is setup in the registry by:

	protected function _initDatabase()
    {
        // Load the multi db plugin
        $resource = $this->getPluginResource('multidb');
        $resource->init();
    
        // set our common database connection used by the
        // models in the /application/models/Common directory.
        Zend_Registry::set('common_db', $resource->getDb('common'));
    }

At the top of each model the folling is used to set the connection:

	protected function _setupDatabaseAdapter()
    {
        // see _initDatabase() in the Bootstrap.php file
        $this->_db = Zend_Registry::get('common_db');
        parent::_setupDatabaseAdapter();
    }
    
    