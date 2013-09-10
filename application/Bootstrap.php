<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{	
	// Our Standard Registry objects (Cadidates for Zend Cache)
	protected $config 			= null; // $this->config->(Option) = applicaiton.ini config option
	protected $db 				= null; // Application DB
	protected $log 				= null; // Logging object
	protected $app 				= null; // Applicaiton specific configuration from shared db.
	
	// local only properties
	protected $Signed_in 		= false; // No user signed in by default
	
	
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
		Zend_Registry::set('shared_db', $resource->getDb('shared'));
	
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
		//Zend_Registry::get('log')->debug("this is a debug log test");	// least severe only shown on FireBug console
		//Zend_Registry::get('log')->info("this is a info log test");
		//Zend_Registry::get('log')->notice("this is a notice log test");
		//Zend_Registry::get('log')->warn("this is a warn log test");
		//Zend_Registry::get('log')->err("this is a err log test");
		//Zend_Registry::get('log')->crit("this is a crit log test");
		//Zend_Registry::get('log')->alert("this is a alert log test");
		//Zend_Registry::get('log')->emerg("this is a emerg log test");	// Most severe 
	}
	
	/***********************************************************************
	 * Load applicaiton specific infromation from the shared databases.
	 * 
	 * This is where we will override any applicaiton.ini configuration with
	 * database driven configuration data.
	 **********************************************************************/
	protected function _initApp()
	{
		$application_model = new Application_Model_Shared_Application();
		$this->app = $application_model->find($this->config->application_id)->current();
		Zend_Registry::set('app', $this->app);
	}
	
	/***********************************************************************
	 * Load the ACL from the appliation.ini file.
	 * 
	 * Format in applicaiton.ini is:
	 * roles = (base role), (parent role):(child role), ..., administrator
	 * 
	 * Example:
	 * roles = view, user:view, admin:user, administrator
	 * 
	 * view - The most basic role.
	 * user - Can do anything view can + anything user can.
	 * admin - Can do anytiing view + user + admin can.
	 * administrator - Specal role that can do anything.
	 **********************************************************************/
	protected function _initACL() {
		//return;
		$this->acl = new Zend_Acl();
	
		$acls = explode(',',$this->config->roles);
		foreach($acls as $acl_pair) {
			$acl = explode(':', $acl_pair);
	
			if (isset($acl[1])) {
				$this->acl->addRole(new Zend_Acl_Role(trim($acl[0])),trim($acl[1]));
			}
			else {
				$this->acl->addRole(new Zend_Acl_Role(trim($acl[0])));
			}
	
			// our prvilages match our roles.
			if (trim($acl[0])!='administrator')
				$this->acl->allow(trim($acl[0]), null, trim($acl[0]));
			else
				$this->acl->allow(trim($acl[0])); // Special role administrator can do anything!!!!
		}
	
		Zend_Registry::set('acl', $this->acl);
	}
	
	
	protected function _initUser() {

		// User table
		$user_model = new Application_Model_Shared_User();
	
		// By default the User is not logged in
		$UserRow=false;
	
		// See if the user is logged in via cookies
		if (isset($_COOKIE['cavuser']) && isset($_COOKIE['cavpad']))
			$UserRow = $user_model->getUserByNameAndPad($_COOKIE['cavuser'],$_COOKIE['cavpad']);
	
		// See if the user is logged in via HTTP BASIC Auth, used mostly for Webservices
		if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW']) {
			$UserRow = $user_model->getUserByNameAndPassword($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
			if (!$UserRow)    // If we have HTTP BASIC Auth but could not sign in with a password try the pad
				$UserRow = $user_model->getUserByNameAndPad($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
		}
	
		if ($UserRow) {
			if ($UserRow->deleted==0) {
				$this->user = $UserRow->toArray();
				$this->user['password']='';    // Obscure the passwords from the data set.
				$this->user['onetimepad']='';  // Obscure the pad from the data set.
				$this->user['salt']='';        // Obscure the salt from the data set
	
				$this->Signed_in=true;
	
				// Set the user_id in the logger
				$this->log->setEventItem('user_id', $this->user['user_id']);
	
				Zend_Registry::set('user', $this->user);
				
				return;
			}
		}
		 
		// We have not logged in the user so redirect the user to the login page.
		Zend_Registry::set('user', null);
	
		// If we have a login server use it to login else use our current server
		if (isset($this->config->login_server)) 
			$LoginURL = "//".$this->config->login_server."/login";
		else 
			$LoginURL = "/login";
		
		// These are ok urls if we are not logged in.
		$login_urls=array('/login','/login/reset','/login/request','/login/forgot');
		if (in_array($_SERVER['REDIRECT_URL'],$login_urls))
			return;
	
		// We were not authenticated and not on a login url so redirect to our login server.
		header( "Location: ".$LoginURL."?ret=".$this->current_url);
		exit();
	}
	
}

