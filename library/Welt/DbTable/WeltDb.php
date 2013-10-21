<?php
class Welt_DbTable_WeltDb extends Zend_Db_Table
{
	/**
	 * Выбор БД welt
	 * @return 
	*/
	protected function _setupDatabaseAdapter()
    {
         $this->_db = Zend_Registry::get('welt');
		 parent::_setupDatabaseAdapter();
    }
}
