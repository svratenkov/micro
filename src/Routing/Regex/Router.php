<?php
/*
	Regex Router uses RegEx for routes
	This router separates `rules` from `actions`. And hanles rules only.
	Action processing is application specific and should be performed by application.
*/
namespace Micro\Routing\Regex;

class Router
{
	/**
	 * Avail HTTP method verbs.
	 * 
	 * @var array	$verbs
	 */
	protected static $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

	/**
	 * Routs URI's RegEx [pattern => [method, action]] array.
	 * 
	 * @var array	$rules
	 */
	protected $routes;

	/**
	 * Initialise new Route instance
	 * 
	 * @param  array			$routes		Array of args to addRoute method
	 * @return void
	 */
	public function __construct($routes = [])
	{
		foreach ($routes as $args) {
			$this->addRoute(...$args);
		}
	}

	/**
	 * Add route rule
	 * 
	 * @param	string|array	$methods	HTTP request methods
	 * @param	string			$pattern	URI RegEx pattern
	 * @param	string|callable	$action		Route action
	 * @return	void
	 */
	public function addRoute($methods, $pattern, $action = NULL)
	{
		foreach ((array) $methods as $key) {
			$this->routes[$key][$pattern] = $action;
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
		foreach ($this->routes[$method] as $pattern => $action) {
			// Check given request for matching current pattern OR 'Match All' special clause
			if ($pattern === '*' or RegexMatcher::compare($uri, $pattern, $params)) {
				// Matched route found! Resolve back references for string action
				if (is_string($action)) {
					RegexMatcher::resolveReferences($action, $params);
				}
				return [$pattern, $action, $params];
			}
		}
	}

	/**
	 * Handle matching route
	 * Возвращает найденный роут Router::$route или NULL
	 * 
	 * @return	void
	public function handle()
	{
		if (is_null($this->matched)) {
			throw new \Exception("Can't handle routing - no matching route found.");
		}

		// Handle callback action if found
		if (is_callable($this->action)) {
echo "Handle callback action...";
			return call_user_func($this->action);
		}

		// Handle controller action if found
		if (is_string($this->action)) {
echo "Handle controller action...";
			RegexMatcher::resolveReferences($this->action, $this->params);
			if (sizeof($parts = explode('@', $this->action)) != 2) {
				throw new \Exception("Incorrect route action `{$this->action}` for pattern `{$this->matched}`.");
			}

			// Need to resolve controller class...
			list($class, $method) = $parts;
			if (! is_null($this->controllerResolver)) {
				$class = $this->controllerResolver($class);
			}
			return call_user_func([$class, $method], ...$this->params);
		}
	}
	 */

	/**
	 * Magic router creators shortcats
	 * 
	 * @param	string	$method	Magic names: get(), post(), ..., any(), all()
	 * @param	array	$args	Received $pattern & $action args
	 * @return	bool			TRUE if executed, FALSE otherwise
	 */
	public function magicAdd($method, $args)
	{
	//	if ($method == 'get') {						// Laravel says: GET === HEAD ?
	//		$verbs = ['GET', 'HEAD'];
	//	}
	//	else
		if ($method == 'all') {						// All avail verbs
			$verbs = self::$verbs;
		}
		else if ($method == 'any') {				// All given verbs
			$verbs = array_shift($args);
			$verbs = array_map('strtoupper', (array) $verbs);
		}
		else if (! in_array($verbs = strtoupper($method), static::$verbs)) {
			return FALSE;
		}

		$this->addRoute($verbs, ...$args);
		return TRUE;
	}

	/**
	 * Magic caller defines get(), post(), ..., any(), all() shortcats
	 * 
	 * @param	string	$method	Notfound method
	 * @param	array	$args	Received args
	 * @return	mixed
	 */
	public function __call($method, $args)
	{
		if ($this->magicAdd($method, $args)) {
			return;
		}

		throw new \ErrorException("<b>Fatal Error:</b> Call to undefined method Router->{$method}()");
	}
}
