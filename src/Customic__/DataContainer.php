<?php
/*
	DataContainer - holder of any configuration params and DI objects
*/
namespace Micro\Customic;

class DataContainer
{
	// Container's data array
	protected $data = [];

	/**
	 * Construct new container instance
	 * 
	 * @param  array	$data	Associative data array
	 * @return void
	 */
	public function __construct($data = [])
	{
		$this->add($data);
	}

	/**
	 * Set item with given key and value pair
	 * 
	 * @param  string	$key	Item key
	 * @param  mixed	$value	Item value
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Mixed {single item}/{items array} setter
	 * Main purpose is to leave pure item set() method ready for overloading in descendants
	 * 
	 * @param  string|array	$key	Item key or items array
	 * @param  mixed		$value	Item value
	 * @return void
	 */
	public function add($key, $value = NULL)
	{
		if (is_array($key)) {
			foreach ($key as $id => $val) {
				$this->set($id, $val);
			}
			return;
		}

		$this->set($key, $value);
	}

	/**
	 * Check if given key exists in the container
	 * 
	 * @return bool
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Get data by given key, or all data array if NULL given
	 * 
	 * @throws \Exception	Key not found
	 * @return array	Data
	 */
	public function get($key)
	{
		try {
			return $this->data[$key];
		} catch (\Exception $e) {
			throw new \Exception("Key `{$key}` not found in the container");
		}
	}

	/**
	 * Gain a value of a given key if exists, otherwise return given $default
	 * Shortcut for:
	 * 	$container->has($key) ? $container->get($key) : $default
	 * 
	 * @param  string	$key
	 * @param  mixed	$default
	 * @return mixed
	 */
	public function gain($key, $default = NULL)
	{
		return $this->has($key) ? $this->get($key) : $default;
	}

	/**
	 * Remove given key from the container
	 * 
	 * @return void
	 */
	public function remove($key)
	{
		if (isset($this->data[$key])) {
			unset($this->data[$key]);
		}
	}

	/**
	 * Magic getter
	 * 
	 * @return mixed	A given key value
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Magic setter
	 * 
	 * @param  mixed	$key	Data key
	 * @param  mixed	$val	Data value
	 * @return void
	 */
	public function __set($key, $val)
	{
		$this->set($key, $val);
	}
}
