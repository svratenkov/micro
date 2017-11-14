<?php
/**
 * SharedItem contains some class name and constructor params.
 * First resolving will replace item value by created singleton instance.
 * Any other resolvings will return the same instance.
 */
namespace Micro\Customic\Resolvers;

class SharedResolver extends FactoryResolver
{
	/**
	 * Get this resolver's mortality flag
	 * 
	 * @return	bool	Mortality flag, TRUE/FALSE for mortal/immortal
	 */
	public function isMortal()
	{
		// Override parent immortality
		// This will couse container to replace resolver with created instance
		return TRUE;
	}
}
