<?php

/**
 * FileTableStorage is used for managing file stored in the /files
 * directory and referenced by the files table.
 * 
 * @author jstormes
 *
 */
class PHPSlickGrid_FileManager_FileTableStorage extends PHPSlickGrid_FileManager_Abstract
{

	public function ProcessFile($file) {

		echo "<pre>\n";
		
		print_r($file);
		 
		echo "</pre>\n";
	}
}