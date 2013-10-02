<?php
/**
 * View helper to upload to the files table and directory
 * using a Twitter Bootstrap modal dialog box.
 */

class PhpSlickGrid_View_Helper_ModalUpload extends Zend_View_Helper_Abstract
{
	public $view;
	public $layout;
	
	public function setView(Zend_View_Interface $view)
	{
		$this->view = $view;
		
		/* The layout is not part of the current view
		 * you have to grab a copy of the layout to
		* change values in the header and footer;
		*/
		$this->layout = Zend_Layout::getMvcInstance();
	}
	
	public function ModalUpload($HTML, $Title="Upload File", $Help="Select the file to upload.", $Action="upload", $Controller=null)
 	{
 		if ($Controller==null)
 			$formAction=$this->view->url(array('action'=>$Action), null, TRUE);
 		else
 			$formAction=$this->view->url(array('action'=>$Action,'controller'=>$Controller), null, TRUE);
 		
		$output = @"   
  <!-- Modal Upload -->
  <div class='modal fade' id='ModalUpload' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>
    <div class='modal-dialog'>
      <div class='modal-content'>
	    <form action='$formAction' method='post' enctype='multipart/form-data'>
	      <div class='modal-header'>
	        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
	        <h4 class='modal-title'>$Title</h4>
	      </div>
	      <div class='modal-body'>	
			<input class='btn btn-default' type='file' name='uploadedfile'>
			<p class='help-block'>$Help</p>
	      </div>
	      <div class='modal-footer'>
	        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	        <button type='submit' class='btn btn-primary'>Upload File</button>
	      </div>
		</form>	
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->";
		
		$this->layout->modals .= $output;
		
		return "<a href='#ModalUpload' role='button' data-toggle='modal' title='$Title'>$HTML</a>";
	}
}