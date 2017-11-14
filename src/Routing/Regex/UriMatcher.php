<?php
/*
	UriMatcher - Regex URI matcher
	This router separates `rules` from `actions`. And uses only rules.
	Action processing is NOT a task of this class.
*/
namespace Micro\Routing\Regex;

class UriMatcher
{
	/**
	 * Route RegEx patterns sorted by HTTP methods.
	 * 
	 * @var array	$routs
	 */
	protected $patterns;

	/**
	 * @var string	$matched	Matched route pattern or NULL
	 */
	protected $matched;

	/**
	 * @var array	$params		Matched route parameters array - trailing URI segments
	 */
	protected $params = [];

	/**
	 * Initialise new Route instance
	 * 
	 * @param  array			$rules		Routing rules [key => rule] array
	 * @param  object|string	$route		Route instance or class name
	 * 
	 * @return void
	 */
	public function __construct($rules)
	{
		foreach ($rules as $pattern => $action) {
			$this->add($pattern);
		}
	}

	/**
	 * Add URI pattern to given HTTP method section
	 * 
	 * @param	string			$pattern	URI RegEx pattern
	 * @param	string|array	$method		HTTP request method
	 * @return	void
	 */
	public function add($pattern, $method = 'GET')
	{
		foreach ((array) $method as $key) {
			$this->patterns[$key][] = $pattern;
		}
	}

	/**
	 * Поиск роута, соответствующего запросу в массиве правил
	 * Возвращает найденный роут Router::$route или NULL
	 * 
	 * @param	string	$uri	HTTP request URI
	 * @param	string	$method	HTTP request method
	 * @return	bool			TRUE if matching rule found
	 */
	public function match($uri, $method = 'GET')
	{
		foreach ($this->patterns[$method] as $pattern)
		{
			// Compare given request for matching current rule OR 'Match All' special clause
			if ($pattern === '*' or UriMatcher::compare($uri, $pattern, $this->params)) {
				return $this->matched = $pattern;
			}
		}
	}
}
