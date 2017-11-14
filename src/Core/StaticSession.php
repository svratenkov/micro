<?php
/*
	Session stores data between requests
	This class rids from hard getters like:
		isset($_SESSION['key1']...['keyN'])) ? $_SESSION['key1']...['keyN']) : NULL
	by getter with 'dot' notation access:
		Session::get('key1.key2.{...}.keyN')

	Implemented as static class becouse of the global nature of $_SESSION var
!!!	Before using this class initialisation (static::init()) MUST be performed !!!
	Does NOT violate OOP as this class has only one dependency: global $_SESSION
*/
namespace Micro\Core;
use Micro\Core\Arr;

class StaticSession
{
	/**
	 * Initialize class
	 * 
	 * @return void
	 */
	public static function init()
	{
		if (! isset($_SESSION)) {
			session_start();
		}
	}

	/**
	 * Check if given 'dotten' session var exists
	 * 
	 * @param  string  $key
	 * @return void
	 */
	public static function has($key)
	{
		return Arr::has($_SESSION, $key);
	}

	/**
	 * Get given 'dotten' session var value
	 * If var does not exists returns given $default
	 * 
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public static function get($key = NULL, $default = NULL)
	{
		// return all $_SESSION array
		if (is_null($key)) {
			return Arr::get($_SESSION);
		}

		return Arr::get($_SESSION, $key, $default);
	}

	/**
	 * Assign given value to a given 'dotten' session var
	 * 
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		Arr::set($_SESSION, $key, $value);
	}

	/**
	 * Remove given 'dotten' session var
	 * 
	 * @param  string  $key
	 * @return void
	 */
	public static function forget($key)
	{
		Arr::forget($_SESSION, $key);
	}
}
