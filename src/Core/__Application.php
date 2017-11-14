<?php
/*
	Base class for linking Micron with application

	Defines methods Start() & Stop() which are called from index.php
	to initialize & finilize application

	Application could extend this class to define it's actual behavior
	and simply change namespace in the index.php use clause for the App class
*/
namespace Micro\Core;

class __Application //	extends Container
{
	public static::$layout;

	/*
		Before route action - Start application
		Common preparations for any request: global vars, functions, views,...
		Method are called from index.php:
		-	after detecting HTTP request & Current route
		-	before calling any response action
	*/
	public static function before()
	{
		// Session handles some vars from previous session: compiler output, active project name
		session_start();

		// Activate recently used project OR redirect to home page with alert
		if (is_null(ScssProject::activate()) AND Request::$uri !== '/') {
			Request::redirect('/');
		}

		// !!! Must be moved to composer.json
		include \Micron\Core\Path::file('vendor', 'scssphp-master/scss.inc.php');

		// Define application layout view
		$params = Config::get('view/layout');
		static::$layout
			->name($params['name'])
			->with($params['data'])
			->glob($params['glob'])
		;
	}

	/*
		After route acition - Stop application
		Common closings for any request, final response decoration
		Method are called from index.php after response action
	*/
	public static function after($response)
	{
		// Add recently used project name tabs to header
		static::$layout->header->with([
			'projects'	=> ScssProject::$projects,
			'active'	=> ScssProject::$active,
			'watching'	=> ScssDispatcher::$watching,
		]);

		return static::$layout->render();
	}

	/*
		Статический доступ к методам драйвера сервиса
		Геттеры и сеттеры свойств драйвера
	public static function __callStatic($method, $parameters)
	{
	*/
/*
		// Геттеры и сеттеры свойств драйвера
		$cmd = substr($method, 0, 4);
		if ($cmd === 'get_') {
			// Вернуть свойство драйвера
			$prop = substr($method, 4);
			if (isset(static::driver()->$prop)) {
				return static::driver()->{$prop};
			}
		}
		else if ($cmd === 'set_') {
			// Установить свойство драйвера и вернуть его
			$prop = substr($method, 4);
			if (isset(static::driver()->$prop)) {
				return static::driver()->{$prop} = $parameters[0];
			}
		}
		// Статический вызов методов драйвера
	//	return call_user_func_array(array(static::driver(), $method), $parameters);
		return call_user_func_array(array(static::$provider, $method), $parameters);
	}
*/
}
?>