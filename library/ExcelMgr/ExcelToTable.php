<?php

class ExcelMgr_ExcelToTable
{
	
	/** @var Zend_Db_Table */
	public $Batch_Row;
	
	
	public function __construct($batch_id) {
		
		$this->log = Zend_Registry::get('log');
		
		$Batch = new ExcelMgr_Models_ExcelMgrBatch();
		
		$this->batch_id = $batch_id;
		
		$this->Batch_Row=$Batch->find($batch_id)->current();
		
		$this->file_name  = $this->Batch_Row->file_name;
		$this->tmp_name   = $this->Batch_Row->tmp_name;
		$this->tab        = $this->Batch_Row->tab;
		$this->table_name = $this->Batch_Row->table_name;
		$this->project_id = $this->Batch_Row->project_id;
		
		$this->map        = json_decode($this->Batch_Row->map,true);
		
		$this->log->debug($this->batch_id);
		$this->log->debug($this->Batch_Row);
		
		
	}
	
	
	function load2() {
		$this->log->info("Starting Load Batch ".$this->batch_id.".");
		
		$LogTable = new ExcelMgr_Models_ExcelMgrLog();
		
		// Begin Transaction
		//$this->destTable->getAdapter()->beginTransaction();
		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
		
		$this->destTable = new Zend_Db_Table($this->table_name);
		
		$this->log->debug($this->tmp_name);
		
		//  $inputFileType = 'Excel5';
		$inputFileType = 'Excel2007';
		//	$inputFileType = 'Excel2003XML';
		//	$inputFileType = 'OOCalc';
		//	$inputFileType = 'Gnumeric';
		
		/* Create our Excel reader */
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		$worksheetNames = $objReader->listWorksheetNames($this->tmp_name);
		$worksheetInfo = $objReader->listWorksheetInfo($this->tmp_name);
		
		$TotalRows = $worksheetInfo[$this->tab]['totalRows'];
		$LastColumn = $worksheetInfo[$this->tab]['lastColumnLetter'];
		
		$objReader->setLoadSheetsOnly($worksheetNames[$this->tab]);
		
		/**  Create a new Instance of our Read Filter  **/
		$chunkFilter = new ExcelMgr_chunkReadFilter();
		/**  Tell the Reader that we want to use the Read Filter  **/
		$objReader->setReadFilter($chunkFilter);
		
		
		/**  Starting at row 1 read 1 row  **/
		$chunkFilter->setRows(1,1);
		$objPHPExcel = $objReader->load($this->tmp_name);
		$sheetData = $objPHPExcel->getActiveSheet()->rangeToArray("A1:{$LastColumn}1",null,true,true,true);
		$columns = $sheetData[1];
	}
	
	
	function load() {
		$this->log->info("Starting Load Batch ".$this->batch_id.".");
		
		$LogTable = new ExcelMgr_Models_ExcelMgrLog();
		
		// Begin Transaction
		//$this->destTable->getAdapter()->beginTransaction();
		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
		
		$this->destTable = new Zend_Db_Table($this->table_name);
		
		$this->log->debug($this->tmp_name);
		
		// Loop over source records
		/* Database adaptor like Excel file */
		$Excel = new PHPSlickGrid_Excel($this->tmp_name);
		
		
		
		if (isset($_POST['firstRowNames'])) {
			if ($_POST['firstRowNames']==0)
				$Excel->firstRowNames=0;
			else
				$Excel->firstRowNames=1;
		}
		$source_tables=$Excel->listTables();
		$SourceSchema=$Excel->describeTable($source_tables[$this->tab]);
		$SourceData=$Excel->ExcelFetchAllArray($source_tables[$this->tab]);
		
		$map=$this->map;
		
		$this->log->info(print_r($map,true));
		
		$error_cnt = 0;
		
		for ($startRow = 2; $startRow <= 240; $startRow += $chunkSize) {
			foreach($SourceData as $Row=>$Columns){
				$NewRow=$this->destTable->fetchNew();
				foreach($Columns as $SourceColumnName=>$Value) {
					if ($map[$SourceColumnName]!='ignore') {
						//$this->log->info("Copying Column: ".$map[$SourceColumnName]);
						$NewRow->$map[$SourceColumnName]=$dbAdapter->quote($Value);
						
					}
				}
				try {
					// Attempt insert
					$this->log->info("Writing Row");
					$NewRow->project_id=$this->project_id;
					$NewRow->excel_mgr_batch_id=$this->batch_id;
					$NewRow->deleted=1;
					$id=$NewRow->save();
					//$this->log->info("Row $id written.");
				}
				catch (Exception $Ex) {
					// Catch errors
					$error_cnt++;
					
					$log_row = $LogTable->createRow();
					$log_row->excel_mgr_batch_id = $this->batch_id;
					$log_row->row = json_encode($Columns);
					$log_row->msg = $Ex->getMessage();
				}
			}
		}
		
		if ($error_cnt==0) {
			$data = array('deleted' => 0);
			$where = $this->destTable->getAdapter()->quoteInto('excel_mgr_batch_id = ?', $this->batch_id);
			$this->destTable->update($data, $where);
		}
		else {
			$where = $this->destTable->getAdapter()->quoteInto('excel_mgr_batch_id = ?', $this->batch_id);
			$this->destTable->delete($where);
		}
			
		
		
	}
	
	public function log() {
		
		$LogTable = new ExcelMgr_Models_ExcelMgrLog();
		
		$sel = $LogTable->select();
		$sel->where("excel_mgr_batch_id = ?", $this->batch_id);
		
		print_r($LogTable->fetchAll($sel)->toArray(),true);
		
	}
	
}