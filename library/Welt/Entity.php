<?php
/**
 * Базовый класс для доменных объектов
 */
abstract class Welt_Entity implements ArrayAccess
{
	/**
	 * Свойства
	 * @var array
	 */
	protected $_data;
	
	/**
	 * Язык
	 * @var string 
	 */
	protected $_lang;
	
	/**
	 * Свойства, зависящие от языка
	 * @var array 
	 */
	protected $_langDep = array();


	/**
	 * Конструктор: установка параметров
	 * @param array $data [optional]
	 * @return 
	 */
    public function __construct(array $data = null)
	{
		//Устанавливаем свойства
        if (!is_null($data))
		{
            foreach ($data as $name=>$value)
			{
                $this-> {$name} = $value;
            }
        }
		if (Zend_Registry::isRegistered('lang'))
		{
			$this->_lang = Zend_Registry::get('lang');
		}
    }
	
	/**
	 * Получение текущего объекта в виде массива
	 * @return 
	 */
    public function toArray()
	{
        return $this->_data;
    }
	
	/**
	 * Установка свойства
	 * @param string $name
	 * @param string $value
	 * @return 
	 */
    public function __set($name, $value)
	{
		$setter = 'set' . ucfirst($name);	
		if(method_exists($this,$setter))
		{
			return $this->$setter($value);
		}
        elseif (array_key_exists($name, $this->_data))
		{
			$this->_data[$name] = $value;
        }
		else
		{
			throw new Welt_Exception_Db('You cannot set new properties'.' on this object');
		}
    }
	
	/**
	 * Получение свойства
	 * @param string $name
	 * @return 
	 */
    public function __get($name)
	{
		$getter = 'get' . ucfirst($name);
		if(method_exists($this, $getter))
		{
			return $this->$getter();
		}
        elseif (array_key_exists($name, $this->_data))
		{
            return $this->_data[$name];
        }
    }
	
	/**
	 * Проверка существования свойства
	 * @param string $name
	 * @return 
	 */
    public function __isset($name)
	{
        return isset($this->_data[$name]);
    }
	
	/**
	 * Удаление свойства
	 * @param string $name
	 * @return 
	 */
    public function __unset($name)
	{
        if (isset($this->_data[$name]))
		{
            unset($this->_data[$name]);
        }
    }
	
	/**
	 * Получить поле в зависимости от языка
	 * @param string $field 
	 */
	protected function _getLangDep($field)
	{
		if (isset($this->_langDep[$field]) && isset($this->_langDep[$field][$this->_lang]))
		{
			$prop = $this->_langDep[$field][$this->_lang];
			return $this->$prop;
		}
		return NULL;
	}

	
	public function offsetExists($offset)
	{
		return property_exists($this,$offset);
	}

	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	public function offsetSet($offset,$item)
	{
		$this->$offset=$item;
	}

	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}

}
