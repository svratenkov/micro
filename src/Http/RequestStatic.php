<?php
/*
	Http Request
	Http request addressing mode:
		Query-mode:	http://example.com/<base>/<path>/script.php[?var=val[&var=val]]
		SEO-mode:	http://example.com/<base>[/segment]

	Definitions:
		URI						::=	[<scheme>:]<RelativeURI>
		RelativeURI				::=	<scheme-specific-part>[#<fragment>]
		scheme-specific-part	::=	[//<authority>][<path>][?<query>]
		authority				::=	[<userinfo>@]<host>[:<port>]

		URL						::=	[<scheme>:][//<authority>][<base>]<PageRelativeURL>
		<PageRelativeURL>		::=	[<path>][?<query>]				// Relative means: against it's page URL

	See:
	-	https://docs.oracle.com/javase/8/docs/api/java/net/URI.html
	-	https://docs.oracle.com/javase/8/docs/api/java/net/URL.html
	-	http://stackoverflow.com/questions/176264/what-is-the-difference-between-a-uri-a-url-and-a-urn/1984225#1984225
*/
namespace Micro\Http;

class RequestStatic
{
	// Http scheme (protocol) and server
	public static $host;				// Server host
	public static $scheme;				// 'HTTP' | 'HTTPS' | any
//	public static $secure;				// True | False -> 'HTTPS' | 'HTTP'

	// Http URL
	public static $docroot;				// DocRoot abs server path
	public static $base;				// Base path of this site in DocRoot
	public static $query;				// Request URI - path from base to requested page (root path ==> '/')
	public static $segments;			// Path segments array
//	public static $script;				// Script name (with .php ext): <base>/<script-path>/<script>
	public static $redirected;			// Is request redirected by .htaccess?


	// Http method & vars
	public static $method;				// Method: GET, POST, ...
	public static $get;					// GET variables
	public static $post;				// POST variables

	/*
		Parse current request params
	*/
	public static function parse()
	{
		// Server protocol: HTTP / HTTPS
		static::$scheme = static::parseProtocol();
	//	static::$secure = static::$scheme === 'https';

		// Server host
		static::$host = $_SERVER['HTTP_HOST'];

		// DocRoot abs path
		static::$docroot = $_SERVER['DOCUMENT_ROOT'];

		// Is request redirected by .htaccess?
		static::$redirected = isset($_SERVER['REDIRECT_URL']);

		// Request base and sements
		list(static::$base, static::$segments) = static::$redirected ? static::parseRedirect() : static::parseRequest();

		// Empty segments mean home '/'
		if (! isset(static::$segments[0])) {
			static::$segments[0] = '/';
		}

		// Plain request query sring
		static::$query = implode('/', static::$segments);

		// Method GET, POST, ... & variables
		static::$method = $_SERVER['REQUEST_METHOD'];
		static::$get = $_GET;
		static::$post = $_POST;

		// Return request array
		return [static::$query, static::$method, static::$scheme];
	}

	/*
		Server protocol: HTTP / HTTPS
	*/
	public static function parseProtocol()
	{
		$parts = explode('/', $_SERVER['SERVER_PROTOCOL']);
		$protocol = $parts[0];
		if ($protocol !== 'HTTP' and $protocol !== 'HTTPS') {
			throw new \Exception("Unknown server protocol {$protocol}");
		}
		return strtolower($protocol);
	}

	/*
		Parse normal query request (without .htaccess redrecting):
			[http://localhost]/base/path/index.php?page_name

		Return: array[BaseUri, Query-Segments,... ]
	*/
	public static function parseRequest()
	{
		$uri = trim($_SERVER['REQUEST_URI'], '/');

		$parts = explode('?', $uri);
		$base = array_shift($parts);

		$query = isset($parts[0]) ? explode('/', $parts[0]) : [];

		return [ $base, $query ];
	}

	/*
		Parse redirected by .htaccess request:
			[http://localhost]/base/path/page_name

		BaseUrl & PathUrl & Script name
		В случае mod_rewrite м.б. перенаправление на исполняемый файл
		base_url - это первые совпадающие сегменты REQUEST_URI и SCRIPT_NAME
			$_SERVER = [
				"SCRIPT_FILENAME"	=> "C:/Vsd/LocalHost/www/vsd.projectbureau.loc/scssphp/scss-web/public/index.php"
				"REDIRECT_URL"		=> "/scssphp/scss-web/composer/"
				"REQUEST_URI"		=> "/scssphp/scss-web/composer/?var=val"
				"SCRIPT_NAME"		=> "/scssphp/scss-web/public/index.php"
				"PHP_SELF"			=> "/scssphp/scss-web/public/index.php"
			]

		Return: array[BaseUri, Query-Segments,... ]
	*/
	public static function parseRedirect()
	{
		// base_url - the first equal sements of REQUEST_URI & SCRIPT_NAME
		$url_parts = explode('/', trim($_SERVER['REDIRECT_URL'], '/'));
		$script_parts = explode('/', ltrim($_SERVER['SCRIPT_NAME'], '/'));
		$base_parts = $query_parts = [];

		// base_parts - first identical segments of URL and Script, и path_parts - all the rest
		foreach ($url_parts as $key => $segment) {
			if (empty($path_parts) and isset($script_parts[$key]) and $script_parts[$key] == $segment) {
				$base_parts[] = $segment;
			}
			else {
				$query_parts[] = $segment;
			}
		}

		return [ implode('/', $base_parts), $query_parts ];
	}

	/*
		Base URL ЭТОГО сайта - абсолютный или относительный
	*/
	public static function baseUrl($abs = TRUE)
	{
		return ($abs ? static::$scheme.'://'.static::$host : '').(static::$base ? '/'.static::$base : '');
	}

	/*
		URL ЭТОГО сайта для заданного URI - абсолютный или относительный
		Если URI не задан, берем URI запроса
	*/
	public static function url($uri = NULL, $abs = TRUE)
	{
		if (is_null($uri)) {
			$uri = static::$query;
		}
		return static::baseUrl($abs).'/'.trim($uri, '/');
	}

	/**
	 * Redirect to application URI with delay & delay message if given
	 * 
	 * @param  string $uri
	 * @param  int    $delay
	 * @param  string $delay_msg
	 * @return void
	 */
	public static function redirect($uri, $delay = 0, $delay_msg = '')
	{
		$url = static::url($uri);

		if ($delay) {
			// Редирект с задержкой
			header('Refresh: '.$delay.'; URL='.$url);
			if ($delay_msg) {
				echo $delay_msg;
			}
		}
		else {
			// Редирект немедленный
			header('Location: '.$url);
		}
		exit;
	}

	/**
	 * Get the HTTP referrer for the request.
	 *
	 * @return string
	 */
	public static function referrer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
	}

	/**
	 * Determine if the current request is an AJAX request.
	 * Returns true if the request is a XMLHttpRequest.
	 *
	 * It works if your JavaScript library set an X-Requested-With HTTP header.
	 * It is known to work with Prototype, Mootools, jQuery.
	 *
	 * @return bool
	 */
	public static function isAjax()
	{
		return isset($_SERVER['X-Requested-With']) and 'XMLHttpRequest' == $_SERVER['X-Requested-With'];
	}
}
