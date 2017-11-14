<?php
/**
 * SharedRegistry - aliased shared objects (singletones) registry.
 * Initial array could be:
 * 	'alias' => object | 'class_name' | ['class', [constructorParam,...]]
 * SharedRegistry data resolution to object instance is made on the first call only
 * Any subsequent gettings will return the same object instance
 */
namespace Micro\Registries;

class SharedRegistry extends ClassRegistry
{
	/**
	 * Get shared object instance (singletone) of a given alias
	 * Otherwise returns NULL or throws an Exception depending on second arg
	 * On the first time resolves aliased class name to an object of this class
	 * Any subsequent gettings will return the same object instance
	 * SharedRegistry data may contain constructor parameters to create objects:
	 * 	['class', [constructorParam,...]]
	 * 
	 * @param string $alias
	 * @param bool   $throw
	 * @return Object
	 */
	public function get($alias)
	{
		return $this->resolve($alias);
	}

	/**
	 * Resolve given object alias to it's shared instance
	 * Creates object instance and replaces object class with that instance
	 *
	 * @param  string	$alias
	 * @return Object
	 */

	protected function resolve($alias)
	{
		// Get raw (not overloaded by parent) aliased item
		$item = $this->gain($alias);

		// Return instance if aliased item is already resolved
		if (is_object($item)) {
			return $item;
		}

		// If ['class', [constructorParam,...] pattern used, parse it and set item class
		if (is_array($item)) {
			list($item, $params) = $item;
			$this->set($alias, $item);
		}
		else {
			$params = [];
		}

		// Use parent ClassRegistry getter for checking aliased class existence
		if (is_null($class = parent::get($alias))) {
			return;
		} 

		// Aliased class exists - instantiate it and replace alias value
		$this->set($alias, $obj = new $class($params));

		return $obj;
	}

	/**
	 * Call shared object method with given params
	 * 
	 * @param string $alias	Could be [$alias, $method]
	 * @return Object
	 */
	public function call($alias, $method, $params = [])
	{
		if (is_array($alias)) {
			list($alias, $method) = $alias;
		}

		$instance = $this->get($alias);

		return call_user_func_array([$instance, $method], $params ?: []);
	}
}
