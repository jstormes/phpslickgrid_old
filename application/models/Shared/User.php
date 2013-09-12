<?php

class Application_Model_Shared_User extends Zend_Db_Table_Abstract
{

    protected $_name = 'user';
    
    protected function _setupDatabaseAdapter()
    {
        // see _initDatabase() in the Bootstrap.php file
        $this->_db = Zend_Registry::get('shared_db');
        parent::_setupDatabaseAdapter();
    }
    
    public function getUserByNameAndPassword($user_nm,$password) {
        $sel = $this->select();
        $sel->where("user_nm = ? ",$user_nm);
        $UserRow=$this->fetchAll($sel)->current();
        if ($UserRow) {
            if ($UserRow->deleted==false){
                if ($UserRow->password==md5($password.$UserRow->salt)) {
                    return $UserRow;
                }
            }
        }
        return false;
    }
    
    public function getUserByNameAndPad($user_nm,$pad) {
    
        $sel = $this->select();
        $sel->where("user_nm = ? ",$user_nm);
        $sel->where("pad = ? ",$pad);
        $UserRow=$this->fetchAll($sel)->current();
        if ($UserRow) {
            if ($UserRow->deleted==false){
                return $UserRow;
            }
        }
        return false;

    }
    
    public function getUserByID($uid) {
        $user= $this->find($uid)->current();
        if (!($user)) {
            $user=$this->createRow();
            $user['user_nm']='Unknown';
            $user['user_abbr']='UNK';
            $user['user_full_nm']='Unknown User';
        }
        $user->password="";
        $user->salt='';
        return $user;
    }
       
}

