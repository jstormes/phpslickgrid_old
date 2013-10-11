<?php

/**
 * FileTableStorage is used for managing file stored in the /files
 * directory and referenced by the files table.
 * 
 * @author jstormes
 *
 */
class PhpSlickGrid_FileManager_PHPExcelLoader extends PhpSlickGrid_FileManager_Abstract
{
	/** @var Zend_Layout */
	public $layout;
	
	/** @var Zend_View */
	public $modalView;
	
	/** @var Zend_View_Inerface */
	public $view;

	public function __construct(){
		
		/* The layout is not part of the current view
		 * you have to grab a copy of the layout to
		* change values in the header and footer.
		*/
		$this->layout = Zend_Layout::getMvcInstance();
		
		$this->modalView = new Zend_View();
		$this->modalView->setScriptPath( APPLICATION_PATH . '/../library/PhpSlickGrid/FileManager/modals/' );
	}
	
	public function ProcessFile($file, Zend_View_Interface $view) {

		echo "<pre>\n";
		print_r($_POST);
		echo "</pre>\n";
		
		if(isset($_POST['temp_file'])){
			echo "<pre>\n";
			echo "Form Change\n";
			$this->modalView->temp_file=$_POST['temp_file'];
			print_r($this->modalView->temp_file);
			echo "</pre>\n";
		}
		else {
			echo "<pre>\n";
			echo "File Upload\n";
			print_r($file);
			echo "</pre>\n";
				
			echo "<pre>\n";
			$this->modalView->temp_file = tempnam ( sys_get_temp_dir() , "PHPslick" );
			print_r($this->modalView->temp_file);
			copy ($file['tmp_name'],$this->modalView->temp_file);
			echo "</pre>\n";
		}
		
		
		
		$Excel = new PhpSlickGrid_Excel($this->modalView->temp_file);
		
		
		//$source_tables=$Excel->get_tables();
		$this->modalView->source_tables=$Excel->get_tables();
		
		/* Place our modal in with the other modals on current page */
 		$this->layout->modals .= $this->modalView->render('PHPExcelLoader.phtml');
  		echo "<script>\n";
  		echo "$( document ).ready(function() {\n";
  		echo "	$('#testmod').modal();\n";
  		echo "});";
  		echo "</script>\n";
	}
}