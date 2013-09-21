<?php

class Application_Model_DbTable_ProjectUser extends Zend_Db_Table_Abstract
{

    protected $_name = 'project_user';
    protected $_primary = 'project_usr_id';

    public function getSelectedTeamMembersByProjectId($project_id) {
    
        $atbdb = Zend_Registry::get('config')->resources->multidb->db2->dbname;

        $sql = "SELECT 
                    b.user_id,
                    b.user_full_nm
                FROM
                    project_user a
                LEFT JOIN $atbdb.user b on a.uid = b.user_id
                WHERE
                    a.project_id = $project_id
                ORDER BY
                    b.user_full_nm";

        //var_dump($sql);exit;
        $query = $this->getAdapter()->quoteInto($sql,'');
        return $this->getAdapter()->fetchAll($query);
    }

}

