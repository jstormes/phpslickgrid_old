<?php

class PHPSlickGrid_ColumnConfig {

    public $data = array();
    
    public $DbTableClass = null;
    public $PrimaryKeyColumn = null;
    public $TimeStampColumn = null;
    
    public $Columns = array();
    public $Options = array();
    
    public $ProjectIDColumn = false;
    
    public $project_id = null;
    
    public $Plugins = array();
    
    public $CSSFiles = array();
    public $JavascriptFiles = array();
    public $grid_registerPlugin = array();
    
    public $Hidden = array();
    public $ReadOnly = array();
    
    public $PreRegister = array();
    
    public $MetaTable = null;
    
    public $display_hidden = false;
    
    
    function __construct(Zend_Db_Table_Abstract $DbTable=null,$jsonrpc=null,Zend_Db_Table_Abstract $MetaTable=null,$acl=null,$role=null) {
        
    	
    	if ($DbTable!=null) {
            
            // See if we have a meta table entry:
            $this->MetaTable=$MetaTable;
    
            // Save acl
            $this->acl = $acl;
            
            // Save current role
            $this->role = $role;
            
            // Db Table class that is our model
            $this->DbTableClass = get_class($DbTable);
    
            // Get info on our source table
            $info=$DbTable->info();
    
            // Get primary key aka ID Column
            $this->PrimaryKeyColumn = array_shift($info['primary']);
    
            // Get Time Stamp Column
            foreach($info['metadata'] as $Value) {
                if ($Value['DEFAULT']=='CURRENT_TIMESTAMP') {
                    $this->TimeStampColumn=$Value['COLUMN_NAME'];
                    break;
                }
            }
    
            // Get Slickgrid columns from the database table columns.
            foreach($info['cols'] as $DBColumn) {
                $this->Columns[$DBColumn] = new PHPSlickGrid_Column();
    
                // Set the default Slickgrid id for the column = database column name
                $this->Columns[$DBColumn]->id=$DBColumn;
    
                // If this column is the same as the primary key for the table,
                // name the Slickgrid column "#" else the name of the column will match
                // the database column.
                if ($DBColumn==$this->PrimaryKeyColumn) {
                    $this->Columns[$DBColumn]->name="#";
                    $this->Columns[$DBColumn]->sortable=true;
                }
                else
                    $this->Columns[$DBColumn]->name=$DBColumn;
    
                // Set the Slickgrid field to match the database column name.
                $this->Columns[$DBColumn]->field=$DBColumn;
    
                // Save the SQL data type in case it is needed later
                $this->Columns[$DBColumn]->sql_type=$info['metadata'][$DBColumn]['DATA_TYPE'];
    
                // If the database field has a length save it in case it is needed later.
                if ($info['metadata'][$DBColumn]['LENGTH']!='')
                    $this->Columns[$DBColumn]->sql_length=$info['metadata'][$DBColumn]['LENGTH'];
    
                // Set the width to 100 for now
                $this->Columns[$DBColumn]->width=100;
                
                
                
                // Set sortable
                
                
    
                // If this column is not the primary key setup a default width and editor
                if ($DBColumn!=$this->PrimaryKeyColumn) {
                    // if the Zend_Db_Table object has a method named "Options_"+column name
                    // the assume the column is a drop down from a lookup table using the said
                    // method to populate the drop down.
                    if (method_exists($DbTable, "Options_".$DBColumn)) {
                        $this->Columns[$DBColumn]->editor='PHPSlick.Editors.Select';
                        $this->Columns[$DBColumn]->sortable=true;
                        $this->Columns[$DBColumn]->width=100;
                        $this->Columns[$DBColumn]->jsonrpc=$jsonrpc;
                    }
                    else {
                        switch ($info['metadata'][$DBColumn]['DATA_TYPE']) {
                            case "varchar":
                                $this->Columns[$DBColumn]->width=100;
                                $this->Columns[$DBColumn]->editor='Slick.Editors.Text';
                                $this->Columns[$DBColumn]->sortable=true;
                                break;
                            case "text":
                                $this->Columns[$DBColumn]->width=300;
                                //$this->Columns[$DBColumn]->editor='Slick.Editors.Text';
                                $this->Columns[$DBColumn]->editor='Slick.Editors.LongText';
                                $this->Columns[$DBColumn]->sortable=false;
                                break;
                            case "bigint":
                            case "int":
                                $this->Columns[$DBColumn]->width=100;
                                $this->Columns[$DBColumn]->editor='Slick.Editors.Integer';
                                $this->Columns[$DBColumn]->sortable=true;
                                break;
                            case "date":
                                $this->Columns[$DBColumn]->width=160;
                                $this->Columns[$DBColumn]->editor='Slick.Editors.Date';
                                $this->Columns[$DBColumn]->sortable=true;
                                break;
                        }
                    }
                }
                
            }
        }
    }
    
    public function UpdateColumnsFromMeta($project_id,$gridName,$display_hidden=false) {
        
        if($this->MetaTable!=null) {
            $sel = $this->MetaTable->select();
            
            $sel->where("project_id = ?",$project_id);
            $sel->where("grid_nm = ?",$gridName);

            
            $rows = $this->MetaTable->fetchAll($sel);
            
            foreach($rows as $column) {
                
                
                
                if (isset($this->Columns[$column->column_nm])) {
                    
                    
                    
                    if ($column->column_alias_nm)
                        $this->Columns[$column->column_nm]->name=$column->column_alias_nm;
                    if ($column->view_role_nm)
                        $this->Columns[$column->column_nm]->view_role=$column->view_role_nm;
                    if ($column->edit_role_nm)
                        $this->Columns[$column->column_nm]->edit_role=$column->edit_role_nm;
                    
                    // Apply our edit role
                    if ($column->edit_role_nm)
                        if (!$this->acl->isAllowed($this->role,null,$column->edit_role_nm))
                            unset($this->Columns[$column->column_nm]->editor);
                    
                    if ($this->display_hidden==false) {
                        if ($column->visibility!=1) {
                            unset($this->Columns[$column->column_nm]);
                        }
                    }
                    
                    // Apply our role settings
                    if ($column->view_role_nm)
                        if (!$this->acl->isAllowed($this->role,null,$column->view_role_nm)) 
                            unset($this->Columns[$column->column_nm]);
                }
                               
            }
        }
    }
    
    public function ViewHiddenColumns($fieldName,$fieldValue) {
        if ($fieldValue==true) 
            $this->display_hidden=true;
        
        $Checked="";
        if ($this->display_hidden)
            $Checked="checked";
            
        
        $HTML = "<form  method=\"post\">";
        $HTML .= "Display Hidden Columns<input id=\"$fieldName\"  type=\"checkbox\" name=\"$fieldName\" $Checked onclick=\"this.form.submit();\"></input>";
        $HTML .= "</form>";
        
        return $HTML;
    }
    
    public function RegisterPlugin(Slickgrid_Plugins_Abstract $Plugin) {
    
        array_push($this->Plugins,$Plugin);
    
        // Add Any Javascript Files to the array of Javascript files to load
        $this->JavascriptFiles=array_merge($this->JavascriptFiles,$Plugin->Javascript_File);
    
        // Add Any CSS Fiels to the array of CSS files to load
        $this->CSSFiles=array_merge($this->CSSFiles,$Plugin->CSS_Files);
    
        // Add Any grid.registerPlugin js objects to the list of
        // grid.registerPlugin to load.
        $this->grid_registerPlugin=array_merge($this->grid_registerPlugin,$Plugin->grid_registerPlugin);
    
        if (method_exists($Plugin, 'Configuration'))
            $Plugin->Configuration($this);
    
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        //echo "Getting '$name'\n";
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
        'Undefined property via __get(): ' . $name .
        ' in ' . $trace[0]['file'] .
        ' on line ' . $trace[0]['line'],
        E_USER_NOTICE);
        return null;
    }
     
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

}