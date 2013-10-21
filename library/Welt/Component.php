<?php
abstract class Welt_Component implements ArrayAccess
{
	/**
	 * Установка атрибутов
	 * @param array $params 
	 */
	public function setAttributes($params)
	{
		foreach ($params as $param => $value)
		{
			$this->setAttribute($param, $value);
		}
	}
	
	/**
	 * Установка атрибута
	 * @param mixed $param 
	 */
	public function setAttribute($param, $value)
	{
		if (property_exists($this, $param))
		{
			$this->$param = $value;
		}
	}
	
	/**
	 * Magic get
	 * @param string $name
	 * @return mixed 
	 */
	public function __get($name)
	{
		$getter = 'get' . ucfirst($name);
		if(method_exists($this, $getter))
		{
			return $this->$getter();
		}
		elseif (property_exists($this, $name))
		{
			return $this->$name;
		}
	}
	
	/**
	 * Magic set
	 * @param string $name
	 * @param mixed $value
	 * @return type 
	 */
	public function __set($name, $value)
	{
		$setter = 'set' . ucfirst($name);
		if(method_exists($this,$setter))
		{
			return $this->$setter($value);
		}
		elseif (property_exists($this, $name))
		{
			$this->$name = $value;
		}
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
