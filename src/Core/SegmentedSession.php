<?php
/*
	To avoid name conflicts in a $_SESSION array, we use it's segmentation
	Each instance of this class may have it's own segment in a $_SESSION array
	Any call will auto prepend any given key with segment of this instance
*/
namespace Micro\Core;

class SegmentedSession
{
	/**
	* @var string $segment	Initial 'dotted' segment
	*/
	protected $segment;

	/**
	 * Construct session instance
	 * 
	 * @param  string $segment
	 * @return void
	 */
	public function __construct($segment = '')
	{
		StaticSession::init();
		$this->segment($segment);
	}

	/**
	 * Get/Set segment
	 * 
	 * @param  string  $segment
	 * @return string
	 */
	public function segment($segment = NULL)
	{
		if (! is_null($segment)) {
			$this->segment = $segment;
		}

		return $this->segment;
	}

	/**
	 * Prepend segment to a given key
	 * 
	 * @param  string  $key
	 * @return string
	 */
	public function segmented($key = NULL)
	{
		return empty($key) ? $this->segment : $this->segment.'.'.$key;
	}

	/**
	 * Check if given 'dotten' session var exists
	 * 
	 * @param  string  $key
	 * @param  mixed   $val
	 * @return void
	 */
	public function has($key)
	{
		return StaticSession::has($this->segmented($key));
	}

	/**
	 * Get given 'dotten' session var value
	 * If var does not exists returns given $default
	 * 
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function get($key = NULL, $default = NULL)
	{
		return StaticSession::get($this->segmented($key));
	}

	/**
	 * Assign given value to a given 'dotten' session var
	 * 
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function set($key, $value)
	{
		return StaticSession::set($this->segmented($key), $value);
	}

	/**
	 * Remove given 'dotten' session var
	 * 
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		return StaticSession::forget($this->segmented($key));
	}
}
