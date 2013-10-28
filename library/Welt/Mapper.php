<?php
/**
 * Базовый класс для mappers
 */
abstract class Welt_Mapper 
{
	/**
	 * Системный язык
	 * @var string
	 */
	protected $_lang;
	
	/**
	 * Название таблицы для шлюза
	 * @var string 
	 */
	protected $_tableName = null;
	
	/**
	 * Шлюз к таблице
	 * @var Zend_Db_Table_Abstract
	 */
    	protected $_tableGateway = null;
	
	/**
	 * Карта сопоставлений полей в таблице БД свойствам доменного объекта
	 * @var array
	 */
	protected $_map;
	
	/**
	 * Названия полей, которые не нужно обновлять при полном обновлении строки
	 * @var array 
	 */
	protected $_updateIgnoreFields = array();


	/**
	 * Название класса, реализующего шлюз к таблице
	 * @var string 
	 */
	protected $_gatewayClassName = null;
	
	/**
	 * Название класса доменного объекта
	 * @var type string
	 */
	protected $_entityClass = null;
	
	/**
	 * Кэш
	 * @var 
	 */
	protected $_cache;
	
	/**
	 * Карта сопоставлений, определяющая наборы полей (для каждого языка) для каждого поля
	 * @var array 
	 */
	protected $_langDepFieldsMap;
	
	/**
	 * Массив с полями для выборки, установленными в зависимости от системного языка
	 * @var array 
	 */
	protected $_langDepFields;


	/**
	 * Конструктор: установка языка, шлюза таблицы
	 * @param string $lang 
	 */
	public function __construct($lang = '')
	{
		//Установка языка
		if ($lang == '')
		{
			//Если язык установлен в реестре
			if (Zend_Registry::isRegistered('lang'))
			{
				$lang = Zend_Registry::get('lang');
			}
			//Если не установлен, берём язык по умолчанию
			else
			{
				$lang = Zend_Registry::get('settings')->multilang->default;
			}
		}
		$this->_lang = $lang;
		//Установка шлюза таблицы
		if (!is_null($this->_gatewayClassName))
		{
			$this->_tableGateway = new $this->_gatewayClassName($this->_tableName);
		}
		else
		{
			$this->_tableGateway = new Zend_Db_Table($this->_tableName);
		}
		$this->_setLangDepFields();
	}
	
	/**
	 * Установка шлюза таблицы
	 * @return type 
	 */
    	protected function _setGateway($tableGateway)
	{
		$this->_tableGateway = $tableGateway;
	}
	
	/**
	 * Получение установленного шлюза таблицы
	 * @return type 
	 */
    	public function _getGateway()
	{
		return $this->_tableGateway;
	}
	
	/**
	 * Поиск свойства объекта, соответствующего указанному названию поля в БД на
	 * основе заданной карты сопоставления.
	 * @param string $tableField
	 * @return mixed
	 */
	protected function findPropMapByField($tableField)
	{
		//Если не задана карта сопоставления
		$modelProp = false;
		//Проходимся по карте
		foreach ($this->_map as $prop => $field)
		{
			//Если для поля есть сопоставление, получаем название соответствующего свойства
			if ($tableField == $field)
			{
				$modelProp = $prop;
			}
		}
		return $modelProp;
	}
	
	/**
	 * Поиск названия поля в таблице, соответствующего указанному свойству объекта
	 * основе заданной карты сопоставления.
	 * @param string $objectProp
	 * @return mixed
	 */
	protected function findFieldMapByProp($objectProp)
	{
		//Если не задана карта сопоставления
		$tableField = false;
		//Проходимся по карте
		foreach ($this->_map as $prop => $field)
		{
			//Если для свойства есть сопоставление, получаем название поля
			if ($objectProp == $prop)
			{
				$tableField = $field;
			}
		}
		return $tableField;
	}
	
	/**
	 * Получение установленного языка
	 * @return string
	 */
	public function getLang()
	{
		return $this->_lang;
	}
	
	/**
	 * Установка языка
	 * @return string
	 */
	public function setLang($lang)
	{
		$this->_lang = $lang;
	}
	
	/**
	 * Установка кэша
	 */
	public function setCache($cacheName)
	{
		$cacheManager = Zend_Registry::get('cacheManager');
		$cache = $cacheManager->getCache($cacheName);
		if(is_null($cache))
		{
			throw new Welt_Exception_Cache('Uknown cache  '.$cacheName);
		}
		$this->_cache = $cache;
	}
	
	/**
	 * Обновление данных по записи
	 * @param Welt_Entity $model
	 * @param mixed $where
	 * @return type 
	 */
	public function update(Welt_Entity $model)
	{
		//Сбрасываем кэш, если он установлен
		if ($this->_cache instanceof Zend_Cache_Core)
		{
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		$updWhere = $this->_tableGateway->getAdapter()->quoteInto('id = ?', $model->id);
		/*
		 * Проходимся по свойствам модели и формируем массив данных для обновления
		 * с помощью карты сопоставления
		 */
		$modelParams = $model->toArray();
		$data = array();
		foreach ($modelParams as $param => $value)
		{
			/**
			 * Отбираем все параметры, кроме id и указанных в переменной $_updateIgnoreFields
			 */
			if ($param != 'id')
			{
				$tableField = $this->findFieldMapByProp($param);
				if ($tableField !== false && !in_array($tableField, $this->_updateIgnoreFields))
				{
					$data[$tableField] = $value;
				}
			}
		}
		return $this->_tableGateway->update($data, $updWhere);
	}
	
	
	/**
	 * Добавление новой записи в таблицу
 	 * @param Welt_Entity $model
	 * @return type 
	 */
	public function insert(Welt_Entity $model)
	{
		//Сбрасываем кэш, если он установлен
		if ($this->_cache instanceof Zend_Cache_Core)
		{
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		/*
		 * Проходимся по свойствам модели и формируем массив данных по записи
		 * с помощью карты сопоставления
		 */
		$modelParams = $model->toArray();
		$data = array();
		foreach ($modelParams as $param => $value)
		{
			$tableField = $this->findFieldMapByProp($param);
			if ($tableField !== false)
			{
				$data[$tableField] = $value;
			}
		}
		return $this->_tableGateway->insert($data);
	}
	
	
	/**
	 * Удаление записи
	 * @param mixed $model
	 */
	public function delete($model)
	{
		//Сбрасываем кэш, если он установлен
		if ($this->_cache instanceof Zend_Cache_Core)
		{
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		//Если передан объект
		if ($model instanceof Welt_Entity)
		{
			$delWhere = $this->_tableGateway->getAdapter()->quoteInto('id = ?', $model->id);
		}
		//Если передано значение id
		else
		{
			$delWhere = $this->_tableGateway->getAdapter()->quoteInto('id = ?', $model);
		}
		return $this->_tableGateway->delete($delWhere);
	}
	
	/**
	 * #------------------------------------ Набор функций для прямой работы с шлюзом ------------------------#
	 */
	
	public function updateData($data, $where)
	{
		//Сбрасываем кэш, если он установлен
		if ($this->_cache instanceof Zend_Cache_Core)
		{
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		return $this->_getGateway()->update($data, $where);
	}
	
	public function insertData($data)
	{
		//Сбрасываем кэш, если он установлен
		if ($this->_cache instanceof Zend_Cache_Core)
		{
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		return $this->_getGateway()->insert($data);
	}
	
	public function deleteData($where)
	{
		//Сбрасываем кэш, если он установлен
		if ($this->_cache instanceof Zend_Cache_Core)
		{
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		return $this->_getGateway()->delete($where);
	}
	
	/**
	 * #------------------------------------------------------------------------------------------------------#
	 */
	
	
	
	/**
	 * Преобразование строки из выборки в объект основе схемы преобразования, 
	 * заданной в модели
	 * @param Zend_Db_Table_Rowset_Abstract $rowSet
	 * @return
	 */
	public function rowToModel(Zend_Db_Table_Row_Abstract $row)
	{
		//Массив опций для последующего создания модели
		$modelOptions = array();
		//Проходимся по полям в переданной строке
		foreach ($row as $key=>$value)
		{
			/*
			 * Ищем соответствие названия поля таблицы в БД свойству доменного
			 * объекта
			 */
			$modelProp = $this->findPropMapByField($key);
			/**
			 * Если соответствие найдено, то добавляем данное свойство в массив
			 * опций для последующего формирования результирующей модели
			 */
			if ($modelProp !== false)
			{
				$modelOptions[$modelProp] = $value;
			}
		}
		//Создаём модель на основе заданных параметров, возвращаем результат
		$resModel = new $this->_entityClass($modelOptions);
		return $resModel;
	}
	
	/**
	 * Преобразование набора строк из выборки в объект
	 * @param Zend_Db_Table_Rowset_Abstract $rowSet
	 * @return
	 */
	public function rowSetToModels(Zend_Db_Table_Rowset_Abstract $rowSet)
	{
		$models = array();
		foreach ($rowSet as $row)
		{
			$models[] = $this->rowToModel($row);
		}
		return $models;
	}
	
	
	/**
	 * Поулчение названия поля, зависящего от языка
	 * @param string $fieldName 
	 */
	public function getLangDepField($fieldName)
	{
		if (isset($this->_langDepFields[$fieldName]))
		{
			return $this->_langDepFields[$fieldName];
		}
		return NULL;
	}
	
	/**
	 * Установка полей для выборки в зависимости от языка
	 */
	protected function _setLangDepFields()
	{
		if (is_array($this->_langDepFieldsMap) && sizeof($this->_langDepFieldsMap) > 0)
		{
			$langIndex = 1;
			if ($this->_lang == 'ru')
			{
				$langIndex = 0;
			}
			foreach ($this->_langDepFieldsMap as $field=>$langsFields)
			{
				$this->_langDepFields[$field] = $langsFields[$langIndex];
			}				
		}
	}
	
	
}

