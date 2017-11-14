<?php
/**
 * Customic - CUSTOMizable Ioc Container
 * incredibly simple, extremely customizable, fantastically compact and clean!
 * Thanks to amazing SoC (separation of concerns design principle)!
 * 
 * Container items could be of two kinds:
 * 	-	values - any ordinary PHP values;
 * 	-	and... AND... and Resolvers! These are special objects that can
 * 		transform their container item to anything they want!
 * 
 * To add new fuctionality to the container the only thing you need is to
 * define simple Resolver class with only one resolver method! Thats all!
 */
namespace Micro\Customic;

//class Customic extends DataContainer implements CustomicInterface
class Customic extends DotContainer implements CustomicInterface
{
	/**
	 * Construct container instance
	 * 
	 * @param  array	$data	Associative data array
	 * @return void
	 */
	public function __construct($data = [])
	{
	//	parent::__construct($data);
	}

	/**
	 * Combined setter for both ordinary values and resolver items.
	 * Supports an array of items.
	 * 
	 * @param  string|array	$key	Container item key or a packed array of item definitions
	 * @param  mixed		$class	Resolver class or NULL for ordinary value
	 * @param  list			$params	Resolver params packed array
	 * @return void
	 */
	public function add($key, $class = NULL, ...$params)
	{
		// Array setter
		if (is_array($key)) {
			foreach ($key as $id => $params) {
				if (is_array($params)) {
					$class = array_shift($params);
				}
				else {
					// Simplistic form for any ordinary value
					$class = NULL;
					$params = [$params];	// !!! pack ordinary value to params list
				}
				$this->add($id, $class, ...$params);
			}
			return;
		}

		// Single setter
		NULL === $class
			? $this->set($key, ...$params)
			: $this->setResolver($key, $class, ...$params);
	}

	/**
	 * Create new resolver item with given class and params.
	 * Given class name doesn't start with '\' assumes to be
	 * a subclass of Customic\Resolvers\.
	 * 
	 * @param  string	$class	Resolver class name
	 * @param  list ...$params	Resolver constructor params
	 * @return ItemInterface	Resolver instance
	 */
	public function createResolver($class, ...$params)
	{
		if ('\\' !== $class[0]) {
			$class = Resolvers::class.'\\'.$class;
		}

		return new $class(...$params);

	}

	/**
	 * Create new resolver item with given class and params
	 * and set it to the container by given key
	 * 
	 * @param  string	$key	Container item key
	 * @param  string	$class	Resolver class name
	 * @param  list ...$params	Resolver constructor params
	 * @return void
	 */
	public function setResolver($key, $class, ...$params)
	{
		$this->set($key, $this->createResolver($class, ...$params));
	}

	/**
	 * Resolve item with given key in the container
	 * 
	 * 
	 * @param  string	$key	Container item key
	 * @param  list ...$params	List of resolver's params
	 * @return mixed			Container item value
	 */
	public function get($key, ...$params)
	{
		$value = parent::get($key);

		if ($value instanceof CustomicResolverInterface) {
			$resolver = $value;
			$value = $resolver->resolve(...$params);

			// Replace resolver object by its value if it is shared
			if ($resolver->isMortal()) {
				$this->set($key, $value);
				// Resolver will be destroed by PHP if it is unused now
			}
		}

		return $value;
	}

	/**
	 * Retrieve row value (without resolving) for given key
	 * 
	 * @param  string	$key	Container item key
	 * @return mixed			Container item raw value
	 */
	public function raw($key)
	{
		return parent::get($key);
	}

	/**
	 * Magic setter for Customic resolver classes:
	 * 		setSomeResolver($key, ...$params)
	 * is equivalent to:
	 * 		set($key, 'SomeResolver', ...$params)
	 * 
	 * @param  string	$name	Inaccessible method name
	 * @param  array 	$args	Resolver constructor params
	 * @return void
	 */
	public function __call($name, $args)
	{
		if (substr($name, 0, 3) == 'set') {
	 		$class = substr($name, 3);			// Resolver class name
	 		$key = array_shift($args);			// Item key & Resolver params
	 		$this->setResolver($key, $class, ...$args);
		}
	}
}
