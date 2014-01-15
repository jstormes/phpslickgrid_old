<?php

class Application_Model_Grids_GridLink extends PHPSlickGrid_Db_Table
{

    protected $_name = 'grid_link';
    protected $_primary = 'grid_link_id';
    
    protected $_friendlyName = 'Link Grid';


    protected function _setupTableName()
    {
    	/* 
    	 * Force the link table to have integrity with this table.
    	 * 
    	 * That is to say if we have a record in this table (grid_left)
    	 * it MUST have a least one matching records in grid_link for each active
    	 * match definition.
    	 */
    	
    	// select * from grid_left
		// left join grid_link on grid_link.grid_left_id = grid_left.grid_left_id
		// where grid_link.grid_link_id is null 
		
    	// If records
    	
    	/*
    	 * insert into grid_link 
    	 */
    	
    	
    	parent::_setupTableName();
    }
    

}

