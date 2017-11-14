<?php
/*
	Library of static functions for RegEx URI matching

	// Wildcards for segments
	(:num)				// Forcing a URI segment to be any digit
	(:any)				// Allowing a URI segment to be any alpha-numeric string
	(:segment)			// Catching the remaining URI without limitations
	(:all)				// Forcing a URI segment to be any characters
	(<wildcard>?)		// Allowing a <wildcard> to be optional with default value

	// Controller Routing
	'welcome'		=> 'home@index'					// Controller = 'home', Action = 'index'
	'welcome/(:any)'=> 'home@index'					// Controller = 'home', Action = 'index', Args = (:1)
	'txt/(:num)-(:any)/(edit|del)'	=> 'txt@(:3)'	// (:3)	=> (edit|del)
	'api/(v1|v2)/(:num)/(:any).(json|xml)' => 'api.(:1).(:2)@(:3)'	// (:3)	=> (:any)
*/
namespace Micro\Routing\Regex;

class RegexMatcher
{
	// The wildcard patterns supported by the router.
	public static $wildcards = [
		'(:num)'		=> '([0-9]+)',
		'(:any)'		=> '([a-zA-Z0-9\.\-_%=]+)',
		'(:segment)'	=> '([^/]+)',
		'(:all)'		=> '(.*)',
	];

	// The optional wildcard patterns supported by the router.
	public static $optional = [
		'/(:num?)'		=> '(?:/([0-9]+)',
		'/(:any?)'		=> '(?:/([a-zA-Z0-9\.\-_%=]+)',
		'/(:segment?)'	=> '(?:/([^/]+)',
		'/(:all?)'		=> '(?:/(.*)',
	];

	/**
	 * Compare given URI to given RegEx pattern
	 * 
	 * @param	string	$uri		HTTP request URI
	 * @param	string	$pattern	URI RegEx pattern
	 * @param	array&	$params		URI parameters - the trail of URI segments
	 * @return	bool
	 */
	public static function compare($uri, $pattern, &$params)
	{
		$params = [];

		// Буквальное соответствие УРИ?
		if ($uri == $pattern) {
			return TRUE;				// Соответствие!
		}

		// Соответствие УРИ по шаблону регулярного выражения RegEx?
		if (strpos($pattern, '(') !== FALSE) {
			if (preg_match('#^'.static::wildcards($pattern).'$#u', $uri, $params)) {
				// If we get a match we'll return the route and slice off the first parameter match,
				// as preg_match sets the first array item to the full-text match of the pattern
				$params = array_slice($params, 1);
				return TRUE;
			}
		}

		// УРИ этого роута не соответствует запросу
		return FALSE;
	}

	/**
	 * Translate route URI wildcards into regular expressions.
	 *
	 * @param  string  $key
	 * @return string
	 */
	protected static function wildcards($key)
	{
		$search	 = array_keys(static::$optional);
		$replace = array_values(static::$optional);

		// For optional params, first translate the wildcards to their
		// regex equivalent, sans the ")?" ending. We'll add the endings
		// back on when we know the replacement count.
		$key = str_replace($search, $replace, $key, $count);

		if ($count > 0) {
			$key .= str_repeat(')?', $count);
		}

		return strtr($key, static::$wildcards);
	}

	/**
	 * Replace all back-references (:i) in the given source string with their params
	 * 
	 * Controller delegates may use back-references to the action params,
	 * which allows the developer to setup more flexible routes to various
	 * controllers with much less code than would be usual.
	 * 
	 * Can update both source and references
	 * 
	 * !!! For example only! This method is NOT used by this class as it is for action only.
	 *
	 * @return int	Number of replacements
	 */
	public static function resolveReferences(&$source, &$params)
	{
		$total = 0;

		foreach ($params as $key => $value)
		{
			if (! is_string($value))
				continue;

			$search = '(:'.($key + 1).')';

			$source = str_replace($search, $value, $source, $count);

			if ($count > 0) {
				unset($params[$key]);
				$total += $count;
			}
		}

		return $total;
	}
}
