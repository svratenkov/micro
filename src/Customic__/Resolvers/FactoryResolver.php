<?php
/**
 * FactoryResolver contains some class name and constructor params.
 * Each resolving returns new instance of this class and params created.
 */
namespace Micro\Customic\Resolvers;

class FactoryResolver extends AbstractResolver
{
	/**
	 * @var array $params	Resolver object constructor params
	 */
	protected $params;

	/**
	 * Initialize new resolver
	 * 
	 * @param	string	$class	Resolver object class
	 * @param	list ...$params	Resolver object constructor params
	 * @return	void
	 */
	public function __construct($class, ...$params)
	{
		$this->value = $class;
		$this->params = $params;
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
	 * Resolve factory item to new object
	 * 
	 * @param	list ...$params	Resolver item params
	 * @return	mixed
	 */
	public function resolve(...$params)
	{
		return new $this->value(...$this->params);
	}
}
