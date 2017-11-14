<?php
/**
 * ClassRegistry - aliased classes registry: ['alias' => 'class']
 * Concerns:
 * 	- Checks class existence while getting class by its alias
 *  - Makes static method calls by class alias
 */
namespace Micro\Registries;

class ClassRegistry extends BaseRegistry
{
	/**
	 * Whether to throw exceptions on errors?
	 * 
	 * @var bool
	 */
	protected $throwFlag = TRUE;

	/**
	 * Get class name of a given alias if class exists
	 * Otherwise returns NULL or throws an Exception depending on second arg
	 * 
	 * @param string $alias
	 * @return mixed
	 */
	public function get($alias)
	{
		$class = parent::get($alias);

		if (! is_null($class) and class_exists($class)) {
			return $class;
		}

		// Class doesn't exist - make coherent report
		if ($this->throwFlag) {
			$registryClass = get_class($this);

			if (is_null($class)) {
				throw new \Exception("Class alias '{$alias}' doesn't registered in the registry '{$registryClass}'.");
			}

			throw new \Exception("Class '{$class}' is registered in the registry '{$registryClass}' with alias '{$alias}', but doesn't exist.");
		}

		return $class;
	}

	/**
	 * Call given static method with given params for a class with given alias
	 * 
	 * @param  string	$alias
	 * @param  string	$method
	 * @param  array	$params
	 * @return mixed
	 */
	public function callStatic($alias, $method, $params = [])
	{
		// If smth wrong - report coherently
		$class = $this->get($alias);

		// Call ViewModel getter with given params
		$data = call_user_func_array([$class, $method], $params ?: []);

		return $data;
	}


	/**
	 * Get/Set throw state
	 * 
	 * @param  string $key
	 * @return mixed
	 */
	public function throwFlag($state = NULL)
	{
		if (is_null($state)) {
			return $this->throwFlag;
		}

		return $this->throwFlag = (bool) $state;
	}
}
