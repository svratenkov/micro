<?php
/*
	Route
*/
namespace Micro\Routing\Regex;

class Route
{
	// Route uri pattern, may contain regex's or wildcard patterns
	public $pattern;

	// Route action
	public $action;

	// HTTP methods assigned to this route
//	public $methods;

	// Matched request params (regex values)
//	public $params = [];

	/**
	 * Initialize route instance
	 * 
	 * @param	string	$pattern
	 * @param	array	$methods
	 * @param	string	$action
	 * @return	void
	 */
	public function __construct($pattern, $action = NULL)
	{
		$this->pattern	= $pattern;
		$this->action	= $action;
	//	$this->methods	= (array) $methods;
	}
}
