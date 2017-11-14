<?php
/*
	Глобальные хелперы, в основном для шаблонов вьюх
*/
use Micro\Core\Config;
use Micro\Http\Request;
use Mvc\View\ContainerView as View;
use Mvc\View\Renderers\PhpFileRenderer;

//------------------------------------------------------------------------------
//	Хелпер для красивого дампа переменных
//------------------------------------------------------------------------------
//include __DIR__.'/../Error/vardump.php';

//------------------------------------------------------------------------------
//	URL generators
//------------------------------------------------------------------------------

if (! function_exists('url')) {
	/**
	 * Generate an application URL.
	 *
	 * @param  string  $uri
	 * @param  bool    $abs
	 * @param  bool    $secure
	 * @return string
	 */
	function url($uri, $abs = TRUE, $secure = FALSE)
	{
		return Request::url($uri, $abs, $secure);
	}
}

if (! function_exists('asset_url')) {
	/**
	 * Generate an application URL to an asset.
	 
	 *
	 * @param  string  $url
	 * @param  bool    $abs
	 * @param  bool    $secure
	 * @return string
	 */
	function asset_url($uri, $abs = TRUE, $secure = FALSE)
	{
		return url(Config::get('app.assets').'/'.$uri, $abs, $secure);
	}
}

if (! function_exists('uri')) {
	/**
	* Helper: Current query URI
	* 
	* @param  string $uri
	* @return void
	*/
	function uri()
	{
		return Request::$uri;
	}
}

if (! function_exists('redirect')) {
	/**
	* Helper: Redirect to given application URI
	* 
	* @param  string $uri
	* @return void
	*/
	function redirect($uri)
	{
		Request::redirect($uri);
	}
}

if (! function_exists('view')) {
	/**
	* Helper: Create view
	* 
	* @param  string $uri
	* @return void
	*/
	function view($template, $data, $params = NULL)
	{
		return new View($template, $data, $params);
	}
}

if (! function_exists('snippet')) {
	/**
	 * Helper: Return the contents of a given snippet (blade or php template file)
	 * 
	 * @param  string	$template
	 * @return string
	 */
	function snippet($template)
	{
		$dir = PhpFileRenderer::$dir;

		// Try to use '.blade.php' file
		if (($path = PhpFileRenderer::path($template, '.blade.php')) === FALSE) {
			// '.blade.php' not found, try to render '.php' file
			if (($path = PhpFileRenderer::path($template, '.php')) === FALSE) {
				throw new \Exception("Neither '{$template}.blade.php' nor '{$template}.php' snippet found in the templates dir '{$dir}'");
			}
		}

		$code = file_get_contents($path);

		return $code;
	}
}
