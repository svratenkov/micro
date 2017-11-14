<?php
/**
 * ValueResolver contains any value
 * 
 * This item class is for compatibility only. The sympler way is to
 * directly assign a value to some container key:
 * 		container->set('some_key', 'some_value')
 * or:
 * 		container->some_key = 'some_value'
 */
namespace Micro\Customic\Resolvers;

class ValueResolver extends AbstractResolver
{
	/**
	 * Initialize new resolver with given value
	 * 
	 * @param	mixed	$value	Item value
	 * @return	void
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * Resolve item - simply returns it's value
	 * Customic will automatically replace this resolver by it's value
	 * 
	 * @param	list ...$params	Resolver item params
	 * @return	mixed
	 */
	public function resolve(...$params)
	{
		return $this->value;
	}
}
