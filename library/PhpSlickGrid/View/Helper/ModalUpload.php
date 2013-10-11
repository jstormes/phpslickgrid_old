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
		* change values in the header and footer.
		*/
		$this->layout = Zend_Layout::getMvcInstance();
	}
	
	public function ModalUpload($name, $file_class, $options)
 	{
 		/* Set our defaults */
 		$_defaults = array(
 			'HTML'=>'Upload',
 			'Title'=>'Upload File',
 			'Help'=>'Select the file to upload.'
 		);
 		
 		/* Merge our defaults with the options passed in */
 		$options = array_merge($_defaults,$options);
 		
 		/* if we have am uploaded file from the this instance 
 		 * of the class, then instantiate the class to process it.
 		 */
 		if (isset($_FILES[$name])) {
 			if ($_FILES[$name]["error"] > 0)
 			{
 				/* Don't know how to test this JS */
 				echo "<script>\n";
 				echo "alert('Error: " . $_FILES[$name]["error"] . "');\n";
 				echo "</script>\n";
 			}
 			else
 			{	
 				/* Call the logic to process the file */
 				$fileProcessor = new $file_class();
 				$fileProcessor->ProcessFile($_FILES[$name],$this->view);
 			}
 		}
 		
 		/* Variables for our modal template */
 		$Title 	= $options['Title'];
 		$Help 	= $options['Help'];
 		$HTML	= $options['HTML'];
 		
 		/* Calculate the maximum upload size from the *.ini setting */
 		$upload_max_filesize 	= $this->return_bytes(ini_get('upload_max_filesize'));
 		$post_max_size 			= $this->return_bytes(ini_get('post_max_size'));
 		$MAX_FILE_SIZE 			= ($upload_max_filesize<$post_max_size?$upload_max_filesize:$post_max_size)-1;
 		
 		/* HTML Template for the Modal box */
		$modal = @"   
  <!-- Modal Upload -->
  <div class='modal fade' id='$name' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>
    <div class='modal-dialog'>
      <div class='modal-content'>
        <script>
	        function ".$name."_on_submit() {
	          if (typeof document.getElementById('upload').files != 'undefined') {
			    if (document.getElementById('upload').files[0].size > $MAX_FILE_SIZE) {
			      alert('File size is too large.');
			      return false;
			    }
			  }
			  return true;
			}
        </script>
	    <form  method='post' enctype='multipart/form-data' onsubmit='return ".$name."_on_submit()'>
	      <div class='modal-header'>
	        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
	        <h4 class='modal-title'>$Title</h4>
	      </div>
	      <div class='modal-body'>	
    		<!-- Name of input element determines name in the array -->
			<input id='upload' class='btn btn-default' type='file' name='$name'>
			<p class='help-block'>$Help</p>
	      </div>
	      <div class='modal-footer'>
	        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	        <button type='submit' class='btn btn-primary'>Upload File</button>
	      </div>
		</form>	
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->
  <!-- End Modal Upload -->	
		";
		
 		/* Place our modal in with the other modals on current page */
		$this->layout->modals .= $modal;
		
		/* Return a bit of HTML that can trigger this modal */
		return "<a href='#$name' role='button' data-toggle='modal' title='$Title'>$HTML</a>";
	}
	
	/**
	 * Calculate the number of bytes from a php.ini 
	 * setting.
	 *
	 * By: jstormes Oct 8, 2013
	 *
	 * @param unknown $val
	 * @return Ambigous <number, string>
	 */
	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}
	

}