PHPSlickGrid
============

QUICKSTART:
===========

CODE:
-----
First you will need a system with a *AMP (OS, Apache, MySQL PHP) stack.  
Currently we are using Apache 2.2.25, PHP 5.3.27 and MySQL 5.5.32.

You will need to have the Zend Framework 1.12 in your include path.  If 
you are not familiar with the Zend Framework look online for setting it 
up.  We use the Git repository at https://github.com/zendframework/zf1.

The easiest way is to just to copy the Zend Framework into in 
the /library directory of the PHPSlickGrid code.  Your /library 
directory should contain the /Zend directory from the git checkout.

Next, you will also need to clone the submodule in the /public/slickgrid 
directory.  Look online for how to do this if you need to. 

Finally, you will also need to set the environment to “development”.  
It has much more debugging and will not force SSL connections.  It will 
also enable FirePHP debugging (http://www.firephp.org/HQ/Use.html) and SQL 
FirePHP profiling.

See: http://stackoverflow.com/questions/5448943/setenv-application-env-development-htaccess-interacting-with-zend-framework


DATABASES:
----------
To setup the databases you will need to run the sql scripts in the 
/setup directory.

p_slickgrid_dev.sql – Will setup the application database.
P_shared_dev.sql – Will setup the shared database.

LOGGING IN:
-----------
After you setup the database replace the email (guest@stormes.net) address 
in the p_shared_dev.user.user_nm column with what you want to use to login.  
The default password will be "Password!8".  



