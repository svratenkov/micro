<?php
/*
 * CustomicInterface - CUSTOMizable Ioc Container interface
 */
namespace Micro\Customic;

interface CustomicInterface
{
	/**
	 * Create new resolver item with given class and params.
	 * Given class name doesn't start with '\' assumes to be
	 * a subclass of Customic\Items\.
	 * 
	 * @param  string	$class	Resolver class name
	 * @param  list ...$params	Resolver constructor params
	 * @return ItemInterface	Resolver instance
	 */
	public function createResolver($class, ...$params);

	/**
	 * Create new resolver item with given class and params
	 * and set it to the container by given key
	 * 
	 * @param  string	$key	Container item key
	 * @param  string	$class	Resolver class name
	 * @param  list ...$params	Resolver constructor params
	 * @return void
	 */
	public function setResolver($key, $class, ...$params);

	/**
	 * Resolve item with given key in the container
	 * 
	 * @param  string	$key	Container item key
	 * @param  list ...$params	List of resolver's params
	 * @return mixed
	 */
	public function get($key, ...$params);

	/**
	 * Retrieve row value (without resolving) for given key
	 * 
	 * @param  string	$key	Container item key
	 * @return mixed			Container item raw value
	 */
	public function raw($key);
}
