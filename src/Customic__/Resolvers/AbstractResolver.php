<?php
/*
 * Abstract callables resolver item for extending by descendants
 */
namespace Micro\Customic\Resolvers;
use Micro\Customic\CustomicResolverInterface;

abstract class AbstractResolver implements CustomicResolverInterface
{
	/**
	 * @var CustomicInterface $owner	Each resolver may know its owner container
	 */
//	public static $owner;
//	protected $owner;

	/**
	 * Get/set owner container for this resolver item
	 * Caustomic container sets itself as owner after creating any resolver
	 * 
	 * @param	CustomicInterface	$owner	New owner container for this resolver
	 * @return	CustomicInterface			Current owner container for this resolver
	public function owner($owner = NULL)
	{
		// Set owner if given
		if (! is_null($owner)) {
			$this->owner = $owner;
		}
		return $this->owner;
	}
	 */

	/**
	 * @var mixed	$value	Each resolver should have some value
	 */
	protected $value;

	/**
	 * Get this resolver's mortality flag
	 * 
	 * Many resolvers (such as shared,  ordinary PHP types, others)
	 * produce the same value on each resolving. They called `mortal`.
	 * They are destroyed after first resolving and replaced by their values.
	 * However some resolvers (such as factories) produce different values
	 * and could not be destroyed.
	 * 
	 * @return	bool	Mortality flag, TRUE/FALSE for mortal/immortal
	 */
	public function isMortal()
	{
		// Default resolver is mortal
		return TRUE;
	}

	/**
	 * Resolve resolver item
	 * 
	 * @param	list ...$params	Resolver item params
	 * @return	mixed
	 */
	abstract public function resolve(...$params);
}
