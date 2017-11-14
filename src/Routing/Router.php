<?php
/*
	Роутер
*/
namespace Micro\Routing;

class Router
{
	/**
	 * @var  $rules		Route rules array
	 */
	public $rules;

	/**
	 * @var  $request	Route working HTTP request [uri, method, secure]
	 */
	public $request;

	/**
	 * @var  $matched	Matched Route object
	 */
	public $matched;

	/**
	 * Routes can be of various syntax due to this var
	 * 
	 * @var  $routeClass	Route object class name
	 */
	public $routeClass;

	//=============================================================================
	//	Route Matcher
	//=============================================================================

	/**
	 * Initialise new Route instance
	 * 
	 * @param  string $class	Route class name
	 * 
	 * @return void
	*/
	public function __construct($class = NULL)
	{
		$this->class = $class ?: Route::class;
	}

	/**
	 * Поиск роута, соответствующего запросу в массиве правил
	 * Возвращает найденный роут Router::$route или NULL
	 * 
	 * @param  array  $rules	Route rules (routs.php)
	 * @param  array  $request	Current request [$uri, $method, $secure]
	 * 
	 * @return Route  $route	Matched Route object | NULL
	*/
	public function match($rules, $request)
	{
		$this->rules = $rules;
		$this->request = $request;
		$this->matched = NULL;
		$class = $this->class;

		// Call 'before' matching process function if defined in the Route class
		if (is_callable([$class, 'before'])) {
			call_user_func_array($class.'::before', [$rules, $request]);
		}

		// Parse and check each rule for matching given request
		foreach ($rules as $clause => $action)
		{
			
			$route = new $class($clause, $action, $request);

			// Parse route rule
			$route->parse();

			// Compare given request for matching current rule
			if ($route->compare()) {
				return $this->matched = $route;
			}
		}
	}

	//==========================================================================
	//	Route Caller: Execute route action and return response
	//==========================================================================

	/**
	 * Call matched route and return it's response
	 * 
	 * @return mixed
	 */
	public function response()
	{
		if ($this->matched instanceof $this->class) {
			return $this->matched->call();
		}
	}
}
