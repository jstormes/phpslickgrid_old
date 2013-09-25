<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{	
	// Our Standard Registry objects (Cadidates for Zend Cache)
	protected $config 			= null; // $this->config->(Option) = applicaiton.ini config option
	protected $db 				= null; // Application DB
	protected $log 				= null; // Logging object
	protected $app 				= null; // Applicaiton specific configuration from shared db.
	protected $user 			= null; // The user row from the user table in the shared db.

	// local only properties
	protected $Signed_in 		= false; // No user signed in by default
	
	protected $AuthServer 		= ''; // Authencation server, logins are redirected to this server.
	protected $LogInOutURL 		= ''; // URL to login/logout of the applicaitons.
	protected $ProfileURL       = ''; // URL for the user to modify their profile.
	
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
	 * 
	 * log:
	 * --------------------------------------------------------------------------------------------
	 * | log_id  | message     | priority | timestamp  | priorityName | user_id     | request_uri |
	 * --------------------------------------------------------------------------------------------
	 * | Primary | Text string | Numeric  | Time error | String text  | user_id of  | URL of the  |
	 * | Key     | of error    | priority | occured    | of priority  | the user if | request if  |
	 * |         | message.    |          |            |              | available.  | available.  |
	 * --------------------------------------------------------------------------------------------
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
		// http://www.firephp.org/
		if ( APPLICATION_ENV != 'production' ) {
			$writer_firebug = new Zend_Log_Writer_Firebug();
			$this->log->addWriter($writer_firebug);
		}
	
		Zend_Registry::set( 'log', $this->log );
	
		// Examples:
		//Zend_Registry::get('log')->debug("this is a debug log test");	// least severe only shown on FireBug console
		//$this->log->debug("this is a debug log test");	// least severe only shown on FireBug console
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
	 * 
	 * Required Table Structure (Table may contain more columns, but must
	 * contain these):
	 *
	 * app:
     * -------------------------------------------------------------
     * | app_id           | app_nm      | app_sub_domain | deleted |
     * -------------------------------------------------------------
     * | Primary Key Must | Application | Sub-Domain for | Record  |
     * | Must match       | Name        | building URL   | Deleted |
     * | app_id in *.ini  |             |                |         |
     * -------------------------------------------------------------   
	 **********************************************************************/
	protected function _initApp()
	{
		$application_model = new Application_Model_Shared_App();
		$this->app = $application_model->find($this->config->app_id)->current();
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
	
	/***********************************************************************
	 * Setup our login/logout URL and profile URL.  Allow for some other 
	 * server on the same domain to provide login services for multiple 
	 * applicaitons.  This server could also use OAuth, Active Directory, 
	 * etc...
	 * 
	 * As long as the proper cookies are setup to match the shared user table
	 * the user will be "logged on".
	 ***********************************************************************/
	protected function _initAuthServer() {
		// If we have a login server use it to login else use our current server
		if (isset($this->config->login_server))
			$this->AuthServer = $this->config->login_server;
		else
			$this->AuthServer = $_SERVER["HTTP_HOST"];
		
		$this->LogInOutURL = "//".$this->AuthServer."/login";
		$this->ProfileURL  = "//".$this->AuthServer."/login/reset"; // for now we just reset password.
	}
	
	/***********************************************************************
	 * Make sure the user is logged in, and setup the user "object" for use 
	 * by the reset of the application.  This is who the user "is".  We have 
	 * three type of user logins.
	 * 
	 * The first login type is command line where we use a command line user from
	 * the config.  The second type is a cookied user, where the user has a 
	 * valid set of cookies that match the user table from the shared 
	 * database.  The final type of login in a HTTP BASIC Auth, used for 
	 * webservices.
	 * 
	 * NOTE: This security is not hardded or tested.  Needs more research
	 * and though.
	 * 
	 * Required Table Structure (Table may contain more columns, but must
	 * contain these):
	 * 
	 * user:
	 * -------------------------------------------------------------------------------------
	 * | user_id     | user_nm    | password         | salt   | onetimepad       | deleted |
	 * -------------------------------------------------------------------------------------
	 * | Primary Key | user email | MD5(password_txt | Random | Key for cookies  | user    |
	 * |             | address    | +salt)           | string | & password reset | deleted |
	 * -------------------------------------------------------------------------------------
	 **********************************************************************/
	protected function _initUser() {

		// User table
		$user_model = new Application_Model_Shared_User();
	
		// By default the User is not logged in
		$UserRow=false;
		
		// If we are command line attempt to use the command line user from 
		// the config file.
		if (PHP_SAPI == 'cli') {
			if (isset($config->command_line_user))
				$UserRow = $user_model->find($config->command_line_user)->current();
		}
	
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
				$this->user = $UserRow;
				$UserRow->password='';    // Obscure the passwords from the data set.
				$UserRow->salt='';        // Obscure the salt from the data set
	
				$this->Signed_in=true;
	
				// Set the user_id in the logger
				$this->log->setEventItem('user_id', $this->user->user_id);
	
				Zend_Registry::set('user', $UserRow);
				
				return;
			}
		}
		 
		// We have not logged in the user so redirect the user to the login page.
		$this->user=null;
		Zend_Registry::set('user', null);
			
		// These are ok urls if we are not logged in.
		$login_urls=array('/login','/login/reset','/login/request','/login/forgot');
		if (isset($_SERVER['REDIRECT_URL']))
			if (in_array($_SERVER['REDIRECT_URL'],$login_urls))
				return; // we on on an allowed url,
						// so return before we redirect to the lgoin server.
	
		// Get our current_url
		$current_url=urlencode($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
		
		// We were not authenticated and not on a login url so redirect to our login server.
		header( "Location: ".$this->LogInOutURL."?ret=".$current_url);
		exit();
	}
	
	/***********************************************************************
	 * Get the current user role, if there is no current uesr role will be 
	 * null.  This is what the user can do in the applcation.
	 * 
	 * The role for the current user for the current application is mapped 
	 * in the user_app_role mapping table in the shared database.  
	 * 
	 * If the user has no mapping in this table the user does not have 
	 * access to this applicaiton.  Said another way, user access is
	 * granted by the entry in the user_app_role table.
	 * 
	 * Required Table Structure (Table may contain more columns, but must
	 * contain these):
	 * 
	 * user_app_role:
	 * Has forigin key constrants with the user, app and role tables in the
	 * shared database.
	 * ------------------------------------------------------------------
	 * | user_app_role_id | user_id      | app_id      | role_id        |
	 * ------------------------------------------------------------------
	 * | Primary Key      | user id from | app id from | role id from   |
	 * |                  | user table   | app table   | the role table |
	 * ------------------------------------------------------------------
	 * 
	 * role:
	 * ----------------------------------------------------------------
	 * | role_id     | parent_id | app_id      | role_nm              |
	 * ----------------------------------------------------------------
	 * | Primary Key | Parent to | app it from | role name must match |
	 * |             | this role | app table   | roles in the config  |
	 * |             | or null   |             |                      |
	 * ----------------------------------------------------------------
	 **********************************************************************/
	protected function _initRole() {
 		if ($this->user!==null) {
			$user_app_role_model = new Application_Model_Shared_UserAppRole();
			$role_model = new Application_Model_Shared_Role();
			$row=$user_app_role_model->fetchRow($user_app_role_model->select()
					->where('user_id = ?',$this->user['user_id'])
					->where('app_id = ?',$this->config->app_id));
			if ($row) {
				$this->role=$row->findDependentRowset($role_model)->current()->role_nm;
				if ($this->role) {
					Zend_Registry::set('role_nm', $this->role);	// Store the role name, for this user, for this app.
					return;
				}
			}
			// If we are here we have no role name (role_nm).
			$this->log->alert("User ".$this->user['user_nm']." user_id ".$this->user['user_nm'].
					" has no role for application ".$this->app->app_nm." app_id ".$this->app->app_id);
			throw new Exception("This user id has no role for this applicaiton.");
			exit;
 		}
	}
	
	/***********************************************************************
	 * Populate the layout.  See resources.layout.layoutPath in *.ini file.
	 **********************************************************************/
	protected function _initBuildLayout() {
	
		// Bind our css for the layout to the view
		$this->bootstrap('layout');
 		$this->layout = $this->getResource('layout');
 		$this->view = $this->layout->getView();
 		
 		
 		// *******************************************************
 		// * Bootstrap front-end framwork 
 		// * http://getbootstrap.com/
 		// *******************************************************
 		$this->view->headLink()->appendStylesheet('/bootstrap/dist/css/bootstrap.css','screen, print');
 		$this->view->headScript()->appendFile('/js/jquery-1.9.1.min.js');
 		$this->view->headScript()->appendFile('/bootstrap/dist/js/bootstrap.min.js');
 		
 		// Poplate the base css files
 		$this->view->headLink()->appendStylesheet('/css/layout/body.css','screen');    // Bind screen CSS for our layout
 		$this->view->headLink()->appendStylesheet('/css/layout/body-print.css','print'); // Bind print CSS for our layout
 		$this->view->headLink()->appendStylesheet('/css/layout/header.css','screen');    // Bind screen CSS for our header
 		 	
 		// User info to the view
 		$this->view->user = $this->user;
 		
 		// http://fortawesome.github.io/Font-Awesome/
 		$this->view->headLink()->appendStylesheet('/font-awesome/css/font-awesome.css','screen, print'); 
	
 		
 		 		
 		// set the default title from the config
 		$this->view->app_name = $this->config->application_name;    
 		$this->view->title = "Project Name";
	
 		// Watermark to show envirment, helpfull so you don't accidently update production.
 		$this->view->watermark=false;
 		// If watermark is enabled in the config put a background image in the header
 		if ($this->config->watermark==1)
 			$this->view->watermark="/images/layout/".APPLICATION_ENV.".png";
 		//	$this->view->watermark="style=\"background-image:url('/images/layout/".APPLICATION_ENV.".png');background-repeat:repeat-x;background-size:\"";
	
 		// Links for the user toolbar.
 		$this->log->debug($this->ProfileURL);
 		$this->view->LogInOutURL=$this->LogInOutURL;
 		$this->view->ProfileURL=$this->ProfileURL;
 		
 		// Links for the footer.
 		$this->view->copyright_company = $this->config->copyright_company;
 		$this->view->copyright_link = $this->config->copyright_link;
	}
	
	/***********************************************************************
	 * Build the Application menu.
	 *********************************************************************/
	protected function _initAppMenu() {
		if ($this->user) {
			$user_app_role_model = new Application_Model_Shared_UserAppRole();
			$app_model = new Application_Model_Shared_App();
			
			// Get All Applications this user has access to
			$row=$user_app_role_model->fetchRow($user_app_role_model->select()
					->where('user_id = ?',$this->user['user_id']));
			if ($row) {
				$apps=$row->findDependentRowset($app_model);
			}
			
 			$this->view->appMenu = array();
 			$split_hostname=explode(".", $_SERVER['SERVER_NAME']);
 			foreach($apps as $key=>$app) {
 				$this->view->appMenu[$key]['label']=$app['app_nm'];
 				// Build out uri
 				$this->view->appMenu[$key]['uri']="http://".$app['app_sub_domain'].".".$split_hostname[count($split_hostname)-2].".".$split_hostname[count($split_hostname)-1];
 			}
		}
	}
	
	/***********************************************************************
	 * Build the menu.
	 **********************************************************************/
	protected function _initNavigation() {
		if ($this->user) {
			 
			// Add menu as a resource to the acl
			$this->acl->add(new Zend_Acl_Resource('menu'));
	
			// Bind our menu into the view
			$this->menu = new Zend_Navigation($this->config->menu);
			
			$this->view->registerHelper(new PhpSlickGrid_View_Helper_Menu(), 'menu');
			
			//Zend_Registry::set('navigation', $this->menu);
			//Zend_Registry::set('Zend_Navigation', $this->menu);
			
			// Load our role into the menue
			$this->view->navigation($this->menu)->setAcl($this->acl)->setRole(trim($this->role));
	
			// Looks for any navigation pages requiring project_id information and injects
			// the id into the element or removes the element if we have no project_id.
			$pages = $this->menu->findAllBy('params_id', 'PROJECT_ID');
			foreach($pages as &$page){
				if ($this->project_id!=0) {
					if (method_exists($page, 'getParams')) {
						$params = $page->getParams();
						$params['project_id']=$this->project_id;
						$page->setParams($params);
					}
				}
				else {
					$this->menu->removePage($page);
				}
			}
		}
	}
	
	
}

