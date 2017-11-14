<?php
/*
 * Customic resolver item interface
 */
namespace Micro\Customic;

interface CustomicResolverInterface
{
	/**
	 * Get this resolver's mortality flag
	 * 
	 * @return	bool	Mortality flag, TRUE/FALSE for mortal/immortal
	 */
	public function isMortal();

	/**
	 * Resolve resolver item
	 * 
	 * @param	list ...$params	Resolver item params
	 * @return	mixed
	 */
	public function resolve(...$params);
}
