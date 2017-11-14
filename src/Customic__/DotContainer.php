<?php
/*
	DotContainer - "dot" notation for container keys
*/
namespace Micro\Customic;

class DotContainer extends DataContainer
{
	/**
	 * Set item with given "dotted" key and value pair
	 * 
	 * @param  string	$key	Item key
	 * @param  mixed	$value	Item value
	 * @return void
	 */
	public function set($key, $value)
	{
		$array =& $this->data;
		$keys = explode('.', $key);

		// This loop allows us to dig down into the array to a dynamic depth by
		// setting the array value for each level that we dig into. Once there
		// is one key left, we can fall out of the loop and set the value as
		// we should be at the proper depth.
		while (count($keys) > 1) {
			$key = array_shift($keys);

			// If the key doesn't exist at this depth, we will just create an
			// empty array to hold the next value, allowing us to create the
			// arrays to hold the final value.
			if (! isset($array[$key]) or ! is_array($array[$key])) {
				$array[$key] = array();
			}

			$array =& $array[$key];
		}

		$array[array_shift($keys)] = $value;
	}

	/**
	 * Check if given "dotted" key exists in the container
	 * 
	 * @return bool
	 */
	public function has($key)
	{
		// To retrieve the array item using dot syntax, we'll iterate through
		// each segment in the key and look for that value. If it exists,
		// we will set the depth of the array and look for the next segment.
		// Otherwise we will return it.
		$array = $this->data;

		foreach (explode('.', $key) as $segment)
		{
			if (! isset($array[$segment]) or ! array_key_exists($segment, $array)) {
				return FALSE;
			}

			$array = $array[$segment];
		}

		return TRUE;
	}

	/**
	 * Get data by given "dotted" key, or all data array if NULL given
	 * 
	 * @throws \Exception	Key not found
	 * @return array	Data
	 */
	public function get($key)
	{
		$array = $this->data;

		foreach (explode('.', $key) as $segment) {
			if (! is_array($array) or ! isset($array[$segment]) or ! array_key_exists($segment, $array)) {
				throw new \Exception("Key `{$key}` not found in the container");
			}
			$array = $array[$segment];
		}

		return $array;
	}

	/**
	 * Remove given "dotted" key from the container
	 * 
	 * @return void
	 */
	public function remove($key)
	{
		$array =& $this->data;
		$keys = explode('.', $key);

		// This loop functions very similarly to the loop in the "set" method.
		// We will iterate over the keys, setting the array value to the new
		// depth at each iteration. Once there is only one key left, we will
		// be at the proper depth in the array.
		while (count($keys) > 1) {
			$key = array_shift($keys);

			// Since this method is supposed to remove a value from the array,
			// if a value higher up in the chain doesn't exist, there is no
			// need to keep digging into the array, since it is impossible
			// for the final value to even exist.
			if (! isset($array[$key]) or ! is_array($array[$key])) {
				return;
			}

			$array =& $array[$key];
		}

		unset($array[array_shift($keys)]);
	}
}
