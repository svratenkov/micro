<?php
/*
	Роут

	Синтаксис правил определения роута по сегментам URI запроса:
		{rule}		:= {clause} => {action}					// Общая форма правила: Условие => Ответ
		{clause}	:= {segments}^{protocol}{method}		// Общая форма условия
		{clause}	:= ^{segments}							// Спец форма условия для POST (с дефолтным протоколом)
		{protocol}	:= ['$'|'#'|'@'|'']						// [ HTTPS | HTTP | Any | default (Route::$default_protocol) ]
		{method}	:= ['GET'|'POST'|'']					// [ GET | POST | GET (default) ]
		{wildcard}	:= [(:num)|(:any)|(:all)|(:segment)|*]	// [ digits | alpha-numeric | remaining URI | '/' any chars '/' | anything ]
		{wildcard?}	:= {wildcard?}							// [ optional with default value ]
		{action}	:= [Closure|{controller}]({args})		// Ответ: PHP function | Controller
		{controller}:= 'controller@action'					// 
		{args}		:= 

	Примеры определения роута:
		// Protocols
		'user/login^[ '#' | '$' | '@' | '' ]'			// HTTP | HTTPS | Any | absence (default_proocol)

		// Methods
		'^user/login'									// POST prefix - special case
		'user/login^{method}'							// {method}: [GET|POST|*], '*' - any method - special case
		'user/login^'									// POST - special case
		'user/login'									// default_method (GET)

		// Wildcards for segments
		'user/(:num)'	=> function($id)				// Forcing a URI segment to be any digit
		'user/(:any)'	=> function($name)				// Allowing a URI segment to be any alpha-numeric string
		'page/(:all)'	=> function($path)				// Catching the remaining URI without limitations
		'page/(:any?)'	=> function($page = 'index')	// Allowing a URI segment to be optional with default value

		// Controller Routing
		'welcome'		=> 'home@index'					// Controller = 'home', Action = 'index'
		'welcome/(:any)'=> 'home@index'					// Controller = 'home', Action = 'index', Args = (:1)
		'txt/(:num)-(:any)/(edit|del)'	=> 'txt@(:3)'	// (:3)	=> (edit|del)
		'api/(v1|v2)/(:num)/(:any).(json|xml)' => 'api.(:1).(:2)@(:3)'	// (:3)	=> (:any)
*/
namespace Micro\Routing;
use Micro\Routing\Route;

class Route
{
	// Rule clause, action, counter in rules array
	public $clause;
	public $action;
	public $cnt;


	// 'Request pattern' [$uri, $method, $secure] to be matched by current request
	public $matcher;

	// Current request to be compared with matcher
	public $request;

	// Current request params (the trail of matching URI) - ViewModel getter params
	public $params = [];

	// This route rule counter in the rules array
	public static $counter = 0;

	// Default secure (http:// or https://) protocol used by application
//	public static $default_secure = FALSE;			// http:// only

	// The wildcard patterns supported by the router.
	public static $patterns = [
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
	 * Initialize route instance
	 * 
	 * @param  string $clause
	 * @param  string $action
	 * 
	 * @return void
	 */
	public function __construct($clause, $action, $request)
	{
		$this->clause	= $clause;
		$this->action	= $action;
		$this->request	= $request;
		$this->cnt		= ++static::$counter;
	}

	//--------------------------------------------------------------------------
	//	Route clause parser
	//--------------------------------------------------------------------------

	/**
	 * Route rule parser
	 * 
	 * @return void
	 */
	public function parse()
	{
		// Clause parser - obtaines allowed [uri, method, protocol] 
		$this->parseClause();
	}

	/**
	 * Rule clause parser
	 * Obtains three request components [uri, method, protocol]
	 * 
	 * @param  string $clause
	 * 
	 * @return void
	 */
	public function parseClause()
	{
		if (($uri = $this->clause) === '*') {		// 'Match All' special clause?
		//	$uri = '(.*)';
			return;
		}

		$method = 'GET';
		$secure = FALSE;

		if (substr($uri, 0, 1) === '^') {
			// УРИ может начинаться спец символом '^' для HTTP:POST
			$method = 'POST';
			$uri = substr($uri, 1);
		}
		else if (($pos = strrpos($uri, '^')) !== FALSE) {
			// УРИ может завершаться методом доступа: ^POST, или просто спец символом '^' для HTTP:POST
			if (! ($method = substr($uri, $pos + 1))) {
				$method = 'POST';
			}
			else {
				// Задан метод - сначала спец-символ протокола
				$protocol = substr($method, 0, 1);
				if		($protocol === '#')	$secure = FALSE;	// http:// only
				else if	($protocol === '$')	$secure = TRUE;		// https:// only
				else						$secure = '*';		// any

				if (! empty($protocol)) {
					$method = substr($method, 1);
				}
			}

			$uri = substr($uri, 0, $pos);
		}
	//	else {
			// Метода нет - GET по умолчанию, УРИ остается исходным
	//	}

		$this->matcher = [$uri, $method, $secure];
	}

	//--------------------------------------------------------------------------
	//	Route clause matcher
	//--------------------------------------------------------------------------

	/**
	 * Compare given request to route rule
	 * 
	 * @return bool
	 */
	public function compare()
	{
		if ($this->clause === '*') {		// 'Match All' special clause?
		//	$uri = '(.*)';
			return TRUE;
		}

		list($uri, $method, $secure) = $this->request;

		// Протокол?
		if ($this->matcher[2] !== '@' and $this->matcher[2] !== $secure) {
			return FALSE;				// протокол правила не соответствует запросу
		}

		// Метод?
		if ($this->matcher[1] !== '*' and $this->matcher[1] !== $method) {
			return FALSE;				// метод правила не соответствует запросу
		}

		// Буквальное соответствие УРИ?
		if (($pattern = $this->matcher[0]) === $uri) {
			return TRUE;				// Соответствие!
		}

		// Соответствие УРИ по шаблону регулярного выражения RegEx?
		if (strpos($pattern, '(') !== FALSE)
		{
			if (preg_match('#^'.static::wildcards($pattern).'$#u', $uri, $params)) {
				// If we get a match we'll return the route and slice off the first parameter match,
				// as preg_match sets the first array item to the full-text match of the pattern
				$this->params = array_slice($params, 1);
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

		return strtr($key, static::$patterns);
	}

	//=============================================================================
	//	Matched route action caller
	//=============================================================================

	/**
	 * Execute the route action and return the response.
	 *
	 * @return mixed
	 */
	public function call()
	{
		// If the route action is a Closure, we will try to call it directly.
		if ($this->action instanceof \Closure) {
			$response = call_user_func_array($this->action, $this->params);
		}
		else {
			// Make action call
			$response = $this->callAction();
		}

		return $response;
	}

	/**
	 * Prepare rule action, call it and return it's response
	 *
	 * @return mixed
	 */
	public function callAction()
	{
		// Action parser - reserved for class extenders
		$this->parseAction();

		// Resolve all back-references '(:i)' in the in the rule action
		$this->resolveActionReferences();

		// Directly call (invoke) route action
		$response = $this->invokeAction();

		return $response;
	}

	/**
	 * Rule action parser
	 * Reserved for class extenders
	 * 
	 * @return void
	 */
	public function parseAction()
	{
	}

	/**
	 * Replace all back-references in the rule action
	 *
	 * @return void
	 */
	public function resolveActionReferences()
	{
		static::resolveReferences($this->action, $this->params);
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
	 * @return int	Number of replacements
	 */
	protected static function resolveReferences(& $source, & $params)
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

	/**
	 * Directly call (invoke) route action and return it's response
	 *
	 * @return mixed
	 */
	public function invokeAction()
	{
		list($class, $method) = explode('@', $this->action);
	//	static::$route->controller = $class;
	//	static::$route->controller_action = $method;
/*
		// $class может быть алиасом класса
		if (isset(static::$controller_aliases[$class])) {
			$class = static::$controller_aliases[$class];
		}
		$class = static::controllers_ns().$class;
*/
		$controller = new $class();

		// Вызываем действие контроллера и пусть делает что хочет
		$response = call_user_func(array($controller, 'call'), $method, $this->params);

		return $response;
	}

	/*
		Уcтановка/возврат NameSpace контроллеров
	public static function controllers_ns($ns = NULL)
	{
		if (is_null($ns)) {
			// Возврат, если еще не установлен, попробовать из конфига
			if (is_null(static::$controllers_ns)) {
				if (! class_exists(Config::class, FALSE) or is_null($ns = Config::get('app.controllers_ns'))) {
					$ns = 'App\\Controllers';
				}
				static::controllers_ns($ns);
			}
		}
		else {
			// Установка
			static::$controllers_ns = rtrim($ns, '\\').'\\';
		}

		return static::$controllers_ns;
	}
	*/

	/*
		Регистрация контроллера (или массива): алиас => класс
	public static function register_controllers($aliases, $class = NULL)
	{
		if (! is_array($aliases)) {
			$aliases = [ $aliases => $class ];
		}

		foreach ($aliases as $alias => $class) {
			static::$controller_aliases[$alias] = $class;
		}
	}
	*/
}
