<?php
/*
	Container - data holder
*/
namespace Micro\Core;

class Container
{
	// An array of container's data
	public $data = [];

	/*
		Static container's factory
	*/
	public static function make($data = [])
	{
		return new static($data);
	}

	/*
		Constructor
	*/
	public function __construct($data = [])
	{
		$this->data = $data;
	}

	/**
	 * Add a key / value pair to the container data.
	 *
	 * Bound data will be available to the container as variables.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return Container object for chaining
	 */
	public function with($key, $value = null)
	{
		if (is_array($key))
		{
			$this->data = array_merge($this->data, $key);
		}
		else
		{
			$this->data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Get the array of container data
	 *
	 * @return array
	 */
	public function data()
	{
		return $this->data;
	}

	/*
		Магическое преобразование в строку
		!!!! C обработкой исключений - они запрещены в __toString()
	public function __toString()
	{
		try {
			return $this->render();
		}
		catch(\Exception $e) {
			// !!!! the __toString method isn't allowed to throw exceptions, SO we turn them into NULL
			// Возврат НЕ string приведет к ErrorException [ 4096 ]: Method __CLASS__::__toString() must return a string value
			return;

			// Fatal error - здесь можно??? передать свое сообщение об ошибке
		//	trigger_error(Error::$user_msg.$e->getMessage().$e->getTraceAsString());
		}
	}
	*/

	/**
	 * Magic Method for handling dynamic data access.
	 */
	public function __get($key)
	{
		return $this->data[$key];
	}

	/**
	 * Magic Method for handling the dynamic setting of data.
	 */
	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Magic Method for checking dynamically-set data.
	 */
	public function __isset($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Magic Method for handling dynamic functions.
	 *
	 * This method handles calls to dynamic with() helpers.
	 */
	public function __call($method, $parameters)
	{
		if (strpos($method, 'with_') === 0)
		{
			$key = substr($method, 5);
			return $this->with($key, $parameters[0]);
		}

		throw new \Exception("Method [$method] is not defined on the {__CLASS__} class.");
	}
}
