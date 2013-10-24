<?php

class Application_Model_DbTable_Grid extends Zend_Db_Table_Abstract
{

    protected $_name = 'grid';
    protected $_primary = 'grid_id';

    protected $_rowClass = 'PhpSlickGrid_Db_Table_Row';


}

