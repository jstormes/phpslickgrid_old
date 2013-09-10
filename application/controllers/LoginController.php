<?php

class LoginController extends Zend_Controller_Action
{

    private $config = null;
    private $user   = null;
    /**
     * @var Zend_Log
     */
    private $log    = null;
    
    public function init()
    {
        /* Initialize action controller here */
    	$this->config     = Zend_Registry::get('config');
    	$this->user       = Zend_Registry::get('user');

    	$this->log        = Zend_Registry::get('log');
    	
    	// this controller uses classic php sessions
    	@session_start();
    	
    }
    
    /**
     Validate an email address.
     Provide email address (raw input)
     Returns true if the email address has the email
     address format and the domain exists.
     */
    private function validEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex)
        {
            $isValid = false;
        }
        else
        {
            $domain = substr($email, $atIndex+1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64)
            {
                // local part length exceeded
                $isValid = false;
            }
            else if ($domainLen < 1 || $domainLen > 255)
            {
                // domain part length exceeded
                $isValid = false;
            }
            else if ($local[0] == '.' || $local[$localLen-1] == '.')
            {
                // local part starts or ends with '.'
                $isValid = false;
            }
            else if (preg_match('/\\.\\./', $local))
            {
                // local part has two consecutive dots
                $isValid = false;
            }
            else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
            {
                // character not valid in domain part
                $isValid = false;
            }
            else if (preg_match('/\\.\\./', $domain))
            {
                // domain part has two consecutive dots
                $isValid = false;
            }
            else if
            (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                    str_replace("\\\\","",$local)))
            {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/',
                        str_replace("\\\\","",$local)))
                {
                    $isValid = false;
                }
            }
            if ($isValid && !(checkdnsrr($domain,"MX") ||
                     checkdnsrr($domain,"A")))
            {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
    }
    
    private function uuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
    }
    
    
    
	public function forgotAction() {
		$this->view->headTitle('Email Password Reset');
		
		if ($this->getRequest ()->isPost ()) {
			$this->view->email = $this->getRequest ()->getPost ( 'email', '' );
			
			if (isset ( $_POST ['forgot'] )) {
				// http://stackoverflow.com/questions/1218191/how-can-i-make-email-template-in-zend-framework
				
				// create view object
				$message = new Zend_View();
				$message->setScriptPath( APPLICATION_PATH . '/views/scripts/login/emails/' );
				
				// assign values
				$message->assign( 'ip_address',       $_SERVER['REMOTE_ADDR'] );
				$message->assign( 'application_name', $this->config->application_name );
				$message->assign( 'admin_email',      $this->config->admin_email );
				$message->assign( 'admin_name',       $this->config->admin_name );
				$message->assign( 'admin_number',     $this->config->admin_number );
				$message->assign( 'email',            $this->view->email );
				
				// create mail object
				$mail = new Zend_Mail ( 'utf-8' );
				
				// Find the user in our database
				$UserTable = new Application_Model_Shared_User ();
				$sel = $UserTable->select ();
				$sel->where ( "user_nm = ? ", $this->view->email );
				$UserRow = $UserTable->fetchAll ( $sel )->current ();
				if ($UserRow) {
					$message->assign( 'UserRow', $UserRow);
					if ($UserRow->deleted == false) {
						// ****** Active User was found in our user database. ******
						
						// Reset the PAD
						$UserRow->onetimepad=$this->uuid();
						$UserRow->save();
						
						$message->assign( 'reset_link', $_SERVER['SERVER_NAME']."/login/reset?pad=".$UserRow->onetimepad );
						
						// configure email envelope and body
						$mail->addTo ( $this->view->email );
						$mail->setSubject ( 'Password Reset Request' );
						$mail->setFrom ( $this->config->admin_email, $this->config->admin_name );
						$mail->setBodyText ( $message->render ( 'forgot_text.phtml' ) );
						$mail->setBodyHtml ( $message->render ( 'forgot_html.phtml' ) );
						
						$mail->send ();
					}
					else {
						// ****** Deleted user attempted to reset password, notify admin and log ******
						
						// configure email envelope and body
						$mail->addTo ( $this->config->admin_email );
						$mail->setSubject ( 'SECUITY ALERT Deleted User Password Reset!!!' );
						$mail->setFrom ( $this->config->admin_email, $this->config->admin_name );
						$mail->setBodyHtml ( $message->render ( 'deleted_html.phtml' ) );
						
						$mail->send ();
						
						$this->log->alert("Deleted user ".$this->view->email." tried to reset password from IP: ".$_SERVER['REMOTE_ADDR']);
					}
				} 
				else {
					// ****** Non existant user tied to reset password, notify admin and log ******
					
					// configure email envelope and body
					$mail->addTo ( $this->config->admin_email );
					$mail->setSubject ( 'SECUITY ALERT Non-Existing User Password Reset!!!' );
					$mail->setFrom ( $this->config->admin_email, $this->config->admin_name );
					$mail->setBodyHtml ( $message->render ( 'nonuser_html.phtml' ) );
					
					$mail->send ();
					
					$this->log->alert("Password reset for Non-Existing user ".$this->view->email." from IP: ".$_SERVER['REMOTE_ADDR']);
				}
				$this->view->message = "alert('A Password reset email has been sent');\n";
				$this->view->message .= 'window.location = "/login";';
				
			} // Button Forgot Password		
		}
	}

    
    public function indexAction()
    { 
        $this->view->headTitle('Login');
        
        $this->view->message='';
        
        // get our domain for cookies
        $split_hostname=explode(".", $_SERVER['SERVER_NAME']);
        $this->domain=$split_hostname[count($split_hostname)-2].".".$split_hostname[count($split_hostname)-1];
         
         
        // Logout any logged in user ,$_COOKIE['cavpad']
        $redirect=false;
        if (isset($_COOKIE['cavuser'])) {
            setcookie("cavuser",$_COOKIE['cavuser'], time() - 3600, "/", $this->domain);
            $redirect=true;
        }
        if (isset($_COOKIE['cavpad'])) {
            setcookie("cavpad",$_COOKIE['cavpad'], time() - 3600, "/", $this->domain);
            $redirect=true;
        }
        if ($redirect)
            $this->_redirect($_SERVER["REQUEST_URI"]);    // redirect to clear out cookies.
        
        
        if (isset($_SESSION['msg'])) {
            $this->view->message=$_SESSION['msg'];
            $_SESSION['msg']=null;
            unset($_SESSION['msg']);
        }
        
        if ($this->getRequest()->isPost()) {
            $this->view->email=$_POST['email'];
            $this->view->password=$_POST['password'];

            if($this->validEmail($this->view->email)){
                                
                // Login button action
                if (isset($_POST['login'])) {
                    
                    // select from user where email=$email
                    $UserTable = new Application_Model_Shared_User();
                    $sel = $UserTable->select();
                    $sel->where("user_nm = ? ",$this->view->email);
                    $UserRow=$UserTable->fetchAll($sel)->current();
                    if ($UserRow) {
                        if (empty($UserRow->salt)) {
                            $reset_link = "/login/reset?pad=".$UserRow->onetimepad;
                            $_SESSION['msg']='You password has expired. Please enter a new password below and press "Reset Password". Note: your password must contain at least 8 characters, including at least one of each of the following types of characters:  Uppercase letter(s), Special characters (~!@#$%^&()_+-={}|:;<>?,.), Numbers.';
                            $this->_redirect($reset_link);
                        }
                        
                        if ($UserRow->deleted==false){
                            if ($UserRow->password==md5($this->view->password.$UserRow->salt)) {
                                // ************ User is valid ************
                                
                                if ($UserRow->onetimepad==null) {
                                    $UserRow->onetimepad = $this->uuid();
                                    $UserRow->save();
                                }   
                                
                                // Set cookies
                                setcookie("cavuser",$this->view->email, 0, "/", $this->domain);
                                 
                                // Set common one time pad.
                                setcookie("cavpad",$UserRow->onetimepad , 0, "/", $this->domain);
                                 
                                
                                // Redirect to home page
                                if ($_GET['ret']!="")
                                {
                                    $this->_redirect("http://".$_GET['ret']);
                                }
                                else
                                {
                                    $this->_redirect("/");
                                 
                                }
                                exit();
                            }
                        }
                        else
                            $this->view->message="I am sorry, that account is disabled.  You can request access by clicking on the request access button.";
                    }
                    if ($this->view->message=='')
                        $this->view->message="I am sorry, I cannot log you in with these credentials.";
                }
                
            }
            else {
                $this->view->message='I am sorry, that email address appears to be invalid.';
            }
        }
               
    }
    
    public function redirect_msg($msg) {
        // if we are here we don't have a good password reset context so redirect to login.
        $_SESSION['msg']=$msg;

        $split_hostname=explode(".", $_SERVER['SERVER_NAME']);
        if ($this->config->federated==true)
            $domain="http://".$this->config->authenticationsubdomain.".".$split_hostname[count($split_hostname)-2].".".$split_hostname[count($split_hostname)-1]."/login";
        else
            $domain="http://".$split_hostname[count($split_hostname)-3].".".$split_hostname[count($split_hostname)-2].".".$split_hostname[count($split_hostname)-1]."/login";
        //$return = urlencode($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        header( "Location: $domain" );
        exit();
    }
    
    private function passwordCheck($password) {
        
        return (preg_match("/^(?=.*\\d)(?=.*[~!@#$%^&()_+-={}|:;<>?,.])(?=.*[a-z])(?=.*[A-Z]).{8,20}$/", $password));
        
    }
    
    
    
    public function resetAction() {
        
        $UserTable = new Application_Model_Shared_User();
        
        $this->view->headTitle('Password Reset');
        $this->view->enable_navigation=false;     

        if (isset($_SESSION['msg'])) {
            $this->view->message=$_SESSION['msg']."<br /><br />";
            unset($_SESSION['msg']);
        }
        else {
         $this->view->message.="Your password must contain at least 8 characters, including at least one of each of the following types of characters:  Uppercase letter(s), Special characters (~!@#$%^&()_+-={}|:;<>?,.), Numbers.";
        }
        // if cancel then redirect back to once we came.
        if ($this->getRequest()->isPost()) {
            if (isset($_POST['cancel'])) {
                // Redirect to home page
                if (isset($_POST['ret']))
                {
                    $this->_redirect("http://".$_POST['ret']);
                }
                else
                {
                    $this->_redirect("/");
                }
                exit();
            }
        }
        
        // if we are not logged in but have a valid PAD then we are respoing to a password change from an email.
        if (isset($_GET['pad'])) {
            
            $sel = $UserTable->select();
            $sel->where("onetimepad = ? ",$_GET['pad']);
            $UserRow=$UserTable->fetchAll($sel)->current();
            if ($UserRow) {
                if ($UserRow->deleted==false){
                    // *********** We have a valid Pad ***************
                    $this->view->have_pad=true;
                    if ($this->getRequest()->isPost()) {
                        if ($_POST['new_passwd']!==$_POST['ver_passwd']) {
                            $this->view->message="Password don't match please try again.";
                            return;
                        }
                        else {
                            if ($this->passwordCheck($_POST['new_passwd'])){
                                $UserRow->onetimepad=$this->uuid();
                                $UserRow->salt=$this->uuid();
                                $UserRow->password=md5($_POST['new_passwd'].$UserRow->salt);
                                $UserRow->save();
                                $this->redirect_msg("Your password has been changed.  You may login below.");
                            }
                            else {
                                $this->view->message="Your password must contain at least 8 characters, including at least one of each of the following types of characters:  Uppercase letter(s), Special characters (~!@#$%^&()_+-={}|:;<>?,.), Numbers.";
                            }
                                
                        }
                    }                    
                    return;
                } 
            }
        }
        else {
            // We must be logged in allready to change the password
            if ($this->user!=null) {
                if ($this->getRequest()->isPost()) {
                    if ($_POST['new_passwd']!==$_POST['ver_passwd']) {
                        $this->view->message="Password don't match please try again.";
                        return;
                    }
                    
                    $sel = $UserTable->select();
                    $sel->where("onetimepad = ? ",$_COOKIE['cavpad']);
                    $sel->where("user_nm = ? ",$_COOKIE['cavuser']);
                    $UserRow = $UserTable->fetchAll($sel)->current();
                    if ($UserRow->password==md5($_POST['old_passwd'].$UserRow->salt)) {
                        if ($this->passwordCheck($_POST['new_passwd'])){
                            // We are good to chang the password
                            $UserRow->onetimepad=$this->uuid();
                            $UserRow->salt=$this->uuid();
                            $UserRow->password=md5($_POST['new_passwd'].$UserRow->salt);
                            $UserRow->save();
                            $this->redirect_msg("Your password has been changed.  You may login below.");
                        }
                        else {
                            $this->view->message="New Password must be at least 6 characters with one upper case, one lower case and one number.";
                        }
                    } 
                    else {
                        $this->view->message="Your old password did not match.";
                    }
                    
                }
                return;
            }
            $this->view->message="Unknown error please try again.";
        }
        $this->redirect_msg("This password reset request has ben used or has expired.  You may request another password reset below."); 
    }
    
    public function requestAction() {
        
        $this->view->headTitle('Request Access');
        
        $this->view->message='';
        
        // create view object
        $message = new Zend_View();
        $message->setScriptPath( APPLICATION_PATH . '/views/scripts/login/emails/' );
        
        $message->name         = $this->view->name         = $this->getRequest ()->getPost ( 'name', '' );
        $message->email        = $this->view->email        = $this->getRequest ()->getPost ( 'email', '' );
        $message->phone        = $this->view->phone        = $this->getRequest ()->getPost ( 'phone', '' );
        $message->mgr_name     = $this->view->mgr_name     = $this->getRequest ()->getPost ( 'mgr_name', '' );
        $message->mgr_email    = $this->view->mgr_email    = $this->getRequest ()->getPost ( 'mgr_email', '' );
        $message->mgr_phone    = $this->view->mgr_phone    = $this->getRequest ()->getPost ( 'mgr_phone', '' );
        $message->instructions = $this->view->instructions = $this->getRequest ()->getPost ( 'instructions', '' );
        
        if ($this->getRequest()->isPost()) {
            
        	if (isset ( $_POST ['request'] )) {
        		
	            if (strlen($this->view->name)<3) {
	                $this->view->message.='Your name is too short.<br/>';
	            }
	            
	            if(!$this->validEmail($this->view->email)){
	                $this->view->message.='Your email address is not valid.<br/>';
	            }
	            
	            if(!$this->validEmail($this->view->mgr_email)){
	                $this->view->message.='Your manger\'s email address is not valid.';
	            }
	            
	            if ($this->view->message=='') {
	                
 	                $split_hostname=explode(".", $_SERVER['SERVER_NAME']);
 	                $message->domain=$split_hostname[count($split_hostname)-2].".".$split_hostname[count($split_hostname)-1];

// STUB:            $message->activate_link = $_SERVER['SERVER_NAME']."/admin/activate?pad=".$UserRow->onetimepad;
 	                $message->ip_address = $_SERVER['REMOTE_ADDR'];
	                
	                
	                // *****************  Send email for access request **************
	                $mail = new Zend_Mail();
	                
	                $mail->addTo ( $this->config->admin_email );
	                $mail->setSubject("Access request ".$message->domain);
	                $mail->setFrom ( $this->config->admin_email, $this->config->admin_name );
	                
	                $mail->setBodyHtml ( $message->render ( 'request_html.phtml' ) );
	                
	                $mail->send();
	                
	                $this->view->js  = "alert('Your request for access has been sent, you will receive an email when you access is granted.');";
	                $this->view->js .= "window.location = '/login';";
            	}
            }
        }
    }


}

