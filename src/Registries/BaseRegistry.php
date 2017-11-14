<?php
/**
 * AbstractRegistry - abstract keyed data storage
 * Stores any types of data accessed by their keys (aliases)
 */
namespace Micro\Registries;

abstract class BaseRegistry
{
	/**
	 * Regitry data [key => value]
	 * 
	 * @var array
	 */
	protected $data = [];

	/**
	 * Initialize registry with given [key => val] data array
	 * 
	 * @param array  $data
	 * @return void
	 */
	public function __construct($data = [])
	{
		$this->set($data);
	}

	/**
	 * Set given value under given key
	 * 
	 * @param  mixed  $key		Item key or [$key => $value] array
	 * @param  mixed  $value
	 * @return void
	 */
	public function set($key, $value = NULL)
	{
		if (is_array($key)) {
			foreach ($key as $var => $val) {
				$this->set($var, $val);
			}
		}
		else {
			$this->data[$key] = $value;
		}
	}

	/**
	 * Return registry data array
	 * 
	 * @return array
	 */
	public function data()
	{
		return $this->data;
	}

	/**
	 * See if given key exists
	 * 
	 * @param  string $key
	 * @return bool
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Get a value of a given key if exists, otherwise return NULL
	 * 
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		if ($this->has($key)) {
			return $this->data[$key];
		}
	}

	/**
	 * Gain a raw value of a given key if exists, otherwise return given $default
	 * !!! Usually this method doesn't overloaded by descendants and returns a RAW VALUE
	 * Shortcut for:
	 * 	$registry->has($key) ? $registry->get($key) : $default
	 * 
	 * @param  string $key
	 * @return mixed
	 */
	public function gain($key, $default = NULL)
	{
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}
}
