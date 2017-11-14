<?php
/**
 * MethodResolver contains some instance or class name.
 * Each resolving retrieves method as param and returns [object, method] callable array.
 * Applicable to:
 * 	- instance static method 
 * 	- instance dynamic method 
 * 	- class static method 
 * Not applicable to:
 * 	- class dynamic method - an instance should be constructed in advance
 */
namespace Micro\Customic\Resolvers;

class MethodResolver extends AbstractResolver
{
	/**
	 * Initialize new resolver
	 * Save object instance or class name which methods will be resolved
	 * 
	 * @param	mixed	$value	Object instance or class name which methods will be resolved
	 * @return	void
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * Get this resolver's mortality flag
	 * 
	 * @return	bool	Mortality flag, TRUE/FALSE for mortal/immortal
	 */
	public function isMortal()
	{
		// Override parent mortality
		return FALSE;
	}

	/**
	 * Resolve item value to valid callable [value, method]
	 * First param is a method name of this item's value,
	 * which should be an existing instance or static class name
	 * 
	 * @param	list ...$params	Resolver item params, first param is the method name
	 * @return	callable
	 */
	public function resolve(...$params)
	{
		return [$this->value, $params[0]];
	}
}
