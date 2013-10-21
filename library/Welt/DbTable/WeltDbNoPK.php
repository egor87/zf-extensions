<?php
/**
 * Базовый класс для шлюза таблиц без Primary Key
 */
class Welt_DbTable_WeltDbNoPK extends Welt_DbTable_WeltDb {
	
	protected function _setupDatabaseAdapter()
    {
         $this->_db = Zend_Registry::get('welt');
		 parent::_setupDatabaseAdapter();
    }
	
	protected function _setupPrimaryKey()
	{
		return true;
	}
}
