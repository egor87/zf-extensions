<?php
class Welt_Paginator_Adapter_DbSelectMapper extends Zend_Paginator_Adapter_DbSelect {
	
	/**
	 * Mapper
	 * @var Welt_Mapper $mapper
	 */
	 protected $mapper;
	 
	 /**
	  * Конструктор: установка mapper
	  * @param Zend_Db_Select $select
	  * @param Welt_Mapper $mapper 
	  */
	 public function __construct(Zend_Db_Select $select, Welt_Mapper $mapper)
	 {
		 parent::__construct($select);
		 $this->mapper = $mapper;
	 }
	 
	 /**
	  * Получение элементов в виде массива из объектов
      * @param  integer $offset Page offset
      * @param  integer $itemCountPerPage Number of items per page
      * @return mixed
	  */
	 public function getItems($offset, $itemCountPerPage)
	 {
		 //Получаем элементы с помощью mapper'а
		 $this->_select->limit($itemCountPerPage, $offset);
		 $paginatorItems = $this->mapper->_getGateway()->fetchAll($this->_select);
		 //Вызываем метод mapper'а, отвечающий за обработку результата
		 $paginatorItems = $this->mapper->processPaginatorRes($paginatorItems);
		 return $paginatorItems;
	 }
}
