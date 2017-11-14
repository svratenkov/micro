<?php
/**
 * GroupResolver contains a group of resolvers
 */
namespace Micro\Customic\Resolvers;
use Micro\Customic\Customic;

class GroupResolver extends AbstractResolver
{
	/**
	 * Initialize new resolver with given value
	 * 
	 * @param	mixed	$value	Item value
	 * @return	void
	 */
	public function __construct()
	{
		$this->value = new Customic();
	}

	/**
	 * Resolve item - simply returns it's value
	 * Resolver will be automatically destroyed by Customic container
	 * 
	 * @param	list ...$params	Resolver item params
	 * @return	mixed
	 */
	public function resolve(...$params)
	{
		return $this->value;
	}
}
