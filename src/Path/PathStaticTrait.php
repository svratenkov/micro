<?php
/*
	PathStaticTrait - статический доступ к хранилищу алиасов путей к любым "крамбам" - файлам, директориям, URL,...

	Пример использования:
		// File Path.php
		namespace App;
		use Micro\Path\PathAlias;
		use Micro\Path\PathStaticTrait;

		class Path
		{
			// Инициируем класс статического экземпляра
			public static $instance = PathAlias::class;

			use PathStaticTrait;
		}
*/
namespace Micro\Path;

trait PathStaticTrait
{
	// !!! Перенести след 2 строки в класс, использующем этот трейт !!!
	// Инициируем класс статического экземпляра
//	protected static $instance = PathXxx::class;

	/**
	 * Вернуть статический экземпляр объекта класса, или создать его
	 * 
	 * @return object
	 */
	public static function instance()
	{
		if (is_object(static::$instance)) {
			return static::$instance;
		}

		if (! is_string(static::$instance)) {
			$class = static::class;
			$ns = __NAMESPACE__;
			throw new \Exception("You must initialize '{$class}::\$instance' property with the name of some '{$ns}\\PathXxx' class");
		}

		$class = static::$instance;
		static::$instance = new $class();

		return static::$instance;
	}

	/**
	 * Магический вызов методов для статического экземпляра класса
	 * 
	 * @method string $name
	 * @method array  $args
	 * @return object
	 */
	public static function __callStatic($name, $args)
	{
		$instance = static::instance();

		// Avoid indefinite __callStatic() loop
		if (! method_exists($instance, $name)) {
			$class = static::class;
			throw new \Exception("Method '{$name}' does not defined in the class '{$class}'");
		}

		return call_user_func_array([$instance, $name], $args);
	}
}
