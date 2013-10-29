<?php

class Application_Model_DbTable_Project extends Zend_Db_Table_Abstract
{

    protected $_name = 'project';
    protected $_primary = 'project_id';

    protected $_rowClass = 'PHPSlickGrid_Db_Table_Row';


}

