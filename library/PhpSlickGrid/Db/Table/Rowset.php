<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Stormes
 * @package    Stormes_Db
 * @subpackage Table
 * @copyright  Copyright (c) 20013 James Stormes (http://www.stormes.net)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Db_Table_Rowset_Abstract
 */
require_once 'Zend/Db/Table/Rowset/Abstract.php';

/**
 * Developer concrete class that extends Zend_Db_Table_Rowset_Abstract.
 *
 * @category   Stormes
 * @package    Stormes_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2013 James Stormes (http://www.stormes.net)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class PhpSlickGrid_Db_Table_Rowset extends Zend_Db_Table_Rowset_Abstract
{
	
	/**
	 * Convert ar rowset into an array(Key=>Value) sutible
	 * for consumption by the formSelect() view helper.
	 *
	 * By: jstormes Sep 22, 2013
	 *
	 * @param string $ValueField
	 * @return multitype:
	 */
	public function toKeyValue($ValueField){
		
		// @todo This works only if we have iterated through
		// the result set once to instantiate the rows.
		foreach ($this->_rows as $i => $row) {			
			$this->_data[$i] = $row->toArray();
		}
				
		Zend_Registry::get('log')->debug("keytovalue");
		
		$info=$this->getTable()->info();
		if (count($info['primary'])!=1) {
			require_once 'Zend/Db/Table/Row/Exception.php';
			throw new Zend_Db_Table_Row_Exception("Only one field may be set as prmary for toKeyValue().");
		}
		
		$prmary=$info['primary'][1];
		$data = array();
				
		foreach($this->_data as $i => $row) {
			$data[$row[$prmary]]=$row[$ValueField];
		}
		
		Zend_Registry::get('log')->debug($this->_data);
		return $data;

	}
}
