<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{	
	// Our Standard Registry objects (Cadidates for Zend Cache)
	protected $config 			= null; // $this->config->(Option) = applicaiton.ini config option
	protected $db 				= null; // Application DB
	protected $log 				= null; // Logging object
	
	/***********************************************************************
	 * Force SSL for our production enviornment.
	 **********************************************************************/
	protected function _initForceSSL() {
		// if we are command line then just return
		if (PHP_SAPI == 'cli')
			return;
	
		if ( APPLICATION_ENV == 'production' )
		{
			if($_SERVER['SERVER_PORT'] != '443') {
				header('Location: https://' . $_SERVER['HTTP_HOST'] .
				$_SERVER['REQUEST_URI']);
				exit();
			}
		}
	}
	
	/***********************************************************************
	 * Get a copy of our configuration enviornment
	 **********************************************************************/
	protected function _initConfig()
	{
		$this->config = new Zend_Config($this->getOptions(), true);
		Zend_Registry::set('config', $this->config);
	}
	
	/***********************************************************************
	 * Initilize our databases, setup two connections.
	 * 
	 * The default connection will be used by our standard models in 
	 * /application/models/DbTables, and a second connection for our shared
	 * modles in /applicaiton/models/Common.
	 **********************************************************************/
	protected function _initDatabases()
	{
		// Load the multi db plugin
		$resource = $this->getPluginResource('multidb');
		$resource->init();
	
		// set our shared database connection used by the
		// models in the /application/models/Shared directory.
		Zend_Registry::set('shared', $resource->getDb('shared'));
	
		// Stup our default database connection
		$this->bootstrap('db');
		$db = $this->getPluginResource('db');
	
		// force UTF-8 connection
		$stmt = new Zend_Db_Statement_Pdo(
				$db->getDbAdapter(),
				"SET NAMES 'utf8'"
		);
		$stmt->execute();
	
		$dbAdapter = $db->getDbAdapter();
	
		// Query profiler (if enabled and not in production)
		$options = $db->getOptions();
		if ($options['profiler']['enabled'] == true
				&& APPLICATION_ENV != 'production'
		) {
			$profilerClass 	= $options['profiler']['class'];
			$profiler 		= new $profilerClass('All DB Queries');
			$profiler->setEnabled(true);
			$dbAdapter->setProfiler($profiler);
		}
	
		Zend_Registry::set('db', $dbAdapter);
	}
	
	/***********************************************************************
	 * Initilize our logging.  
	 * 
	 * All logging more severe that "DEBUG" is sent to the log table of the
	 * applicaiton database.  Firebug (FirePHP) is only enabled for 
	 * non produciton enviornments. 
	 **********************************************************************/
	protected function _initLogger() {
	
		// Setup logging
		$this->log = new Zend_Log();
		 
		// Add user_id to the logged events
		$this->log->setEventItem('user_id', 0);
		// Add the URI to the logged events
		$this->log->setEventItem('request_uri', $_SERVER["REQUEST_URI"]);
		 
		$writer_db = new Zend_Log_Writer_Db(Zend_Registry::get('db'), 'log');
		$this->log->addWriter($writer_db);
		 
		// Prevent debug messages from going to the DB.
		$filter = new Zend_Log_Filter_Priority(Zend_Log::INFO);
		$writer_db->addFilter($filter);	
		 
		// if we are not in produciton enable Firebug
		if ( APPLICATION_ENV != 'production' ) {
			$writer_firebug = new Zend_Log_Writer_Firebug();
			$this->log->addWriter($writer_firebug);
		}
	
		Zend_Registry::set( 'log', $this->log );
	
		// Examples:
		//Zend_Registry::get('log')->debug("this is a debug log test");	// least severe only shows up on FireBug
		//Zend_Registry::get('log')->info("this is a info log test");
		//Zend_Registry::get('log')->notice("this is a notice log test");
		//Zend_Registry::get('log')->warn("this is a warn log test");
		//Zend_Registry::get('log')->err("this is a err log test");
		//Zend_Registry::get('log')->crit("this is a crit log test");
		//Zend_Registry::get('log')->alert("this is a alert log test");
		//Zend_Registry::get('log')->emerg("this is a emerg log test");	// Most severe 
	}
}

