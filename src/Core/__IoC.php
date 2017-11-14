<?php
/*
	Simple IoC container

	Abilities:
	-	Instantiates given class
	-	Uses rules for instance creation:
		-	'shared'	- Shared instance
		-	'params'	- Constructor params
		-	'alias'		- Class aliase
	Can't resolve constructor's dependencies (use smth like Dice instead)

	Main problem is new() operator which DOES accept only fixed number of args.
	This could be resolved by reflection, see 'dynamic new': create_instance($class, $params)
*/
namespace Micro\Core;
use ReflectionClass;

class IoC
{
	//-----------------------------------------------------------------------------
	// Static access
	//-----------------------------------------------------------------------------

	// singletone
	protected static $singletone;

	/**
	 * Static call given method with given args
	 * 
	 * @param  string  $method
	 * @param  array   $args
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $args)
	{
		if (is_null(static::$singletone)) {
			static::$singletone = new static();
		}
		return call_user_func_array([static::$singletone, $method], $args);
	}

	//-----------------------------------------------------------------------------
	// Dynamic access
	//-----------------------------------------------------------------------------

	/**
	 * 
	 * @var $shared	Shared instancies
	 * 
	 */
	protected $shared = [];

	/**
	 * 
	 * @var $rules	Class rules
	 * 
	 */
	protected $rules = [];

	/**
	 * 
	 * @var $aliases	Class aliases
	 * 
	 */
	protected $aliases = [];

	/**
	 * Construct IOC container with given rules
	 * 
	 * @param  $rules	Predefined IOC Container rules
	 * @return void
	 */
	public function __construct(array $rules = [])
	{
		$this->rules = $rules;
	}

	/**
	 * Create instance of given class using given rules
	 * 
	 * @param  string  $class
	 * @param  array   $rules
	 * @return void
	 */
	public function create($class, $rule = [])
	{
dd(__METHOD__, $class, $rule);
		$params = ! empty($rule['params']) ? $rule['params'] : [];
	//	$instance = new $class($args);
		$instance = $this->creator($class, ...$params);

		return ;
	}

	/**
	 * 'Dynamic new()' - Create instance of a given class with given params
	 * 
	 * @param  string  $class
	 * @param  array   $params
	 * @return void
	 */
	public function creator($class, ...$params)
	{
	//	if (PHP_VERSION < '5.6.0') {
	//		$reflection_class = new ReflectionClass($class);
	//		$instance = $reflection_class->newInstanceArgs($params);
	//	}
		$instance = new $class(...$params);
de(__METHOD__, $class, $params, $instance);

		return $instance;
	}
}
