phpslickgrid
============

PHPSlickGrid


SETUP
=====

CODE:
-----
First you will need a system with a *AMP (OS, Apache, MySQL PHP) stack.  
Currently we are using Apache 2.2.25, PHP 5.3.27 and MySQL 5.5.32.

You will need to have the Zend Framework 1.12 in your include path.  If 
you are not familiar with the Zend framework look online for setting it 
up.  We use the Git repository at https://github.com/zendframework/zf1.

You will also need to clone the submodule in the /public/slickgrid 
directory.  Again look online for how to do this if you need to. 

You will also need to set the environment to “development”.  It has much 
more debugging and will not force SSL connections.  It will also enable 
FirePHP debugging (http://www.firephp.org/HQ/Use.html) and SQL FirePHP 
profiling.

To setup the databases you will need to run the sql scripts in the 
/setup directory.

DATABASES:
----------
p_slickgrid_dev.sql – Will setup the application database.
P_shared_dev.sql – Will setup the shared database.

After you setup the database replace the email address dummy@stormes.net 
with your email address.  The default password will be "Password!8".  
You can use this email and password to login.


