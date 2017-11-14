<?php
/*
	Config - статический сервис массивов конфигурации с точечной нотацией
	-	Файлы конфига сидят в одном дире, заданным переменной dir
	-	Доступ к элементу конфига по ключу c двумя секциями и с точеченой нотацией:
			<key>	::= <file>[<item>]
				<file>	::= {dir/}file
				<item>	::=	{.segment}
	-	Добавлены "переменные" на плэйсхолдерах (':key') и их подстановка в строках и массивах
*/
namespace Micro\Core;

class Config
{
	// Кэш загруженных массивов (!!!не файлов!!!) конфига
	public static $items = array();

	// Базовый директорий файлов конфига для этого кдасса
	public static $dir;


	/*
		Установить/вернуть базовый дир конфигов этого класса
	*/
	public static function dir($dir = NULL)
	{
		if (is_null($dir)) {
			// Вернуть дир конфигов, если же не установлен, попробовать алиас пути конфигов
			if (is_null(static::$dir) and class_exists(Path::class, FALSE) and is_null(static::$dir = Path::get('config'))) {
				throw new \Exception("Config directory is undefined.");
			}
		}
		else {
			// Установить дир конфигов
			if (($path = realpath($dir)) === FALSE) {
				throw new \Exception("Can't find config directory `{$dir}`.");
			}
			static::$dir = $path.DIRECTORY_SEPARATOR;
		}

		return static::$dir;
	}

	/*
		Есть элемент конфига?
	*/
	public static function has($key)
	{
		// Разбор ключа - массив элемента конфига и ключ элемента
		list($items, $item) = static::items($key);

		return isset($items[$item]);
	}

	/*
		Вернуть значение элемента конфига, если его нет - $default
		Если задан массив подстановок, заменить плэйсхолдеры ':key' на заданные значения
	*/
	public static function get($key, $default = NULL, $replacements = NULL)
	{
		// ключ не задан, вернуть весь массив КОНФИГОВ
		if ($key) {
			// Разбор ключа - массив элемента конфига и ключ элемента
			list($items, $item) = static::items($key);

			// Есть ключ элемента с точечной нотацией
			$items = Arr::get($items, $item, $default);
		}
		else {
			$items = static::$items;
		}

		if ($replacements) {
			$items = Arr::replace($items, array_keys($replacements), array_values($replacements));
		}

		return $items;
	}

	/*
		Установить значение элемента конфига
		если ключ не задан, установить весь массив элементов
	*/
	public static function set($key, $value)
	{
		if ($key) {
			// Разбор ключа - массив элемента конфига и ключ элемента
			list($items, $item, $file) = static::items($key);

			// Есть ключ элемента с точечной нотацией
			static::$items[$file] = Arr::set($items, $item, $value);
		}
		else {
			// ключ не задан, установить новый массив КОНФИГОВ
			static::$items = $value;
		}

	//	return $value;
	}

	/*
		Вернуть массив конфига и сегменты для заданного ключа
	*/
	public static function items($key)
	{
		if (! $key) {
			return [[], ''];
		}

		// Разбор ключа - путь к файлу конфига и ключ элемента
		list($file, $item) = static::parse_key($key);

		// Взять массив конфига из кэша
		$items = static::load($file);

		return [$items, $item, $file];
	}

	/*
		Парсер ключа эл-та конфига
		Возвращает массив из двух секций
	*/
	protected static function parse_key($key)
	{
		// Деккомпозируем ключ - в нем м.б. путь от трека: <app/db/odbc.test>
		if (($pos = strpos($key, '.')) === FALSE) {
			// Только имя файла - запрос всего массива конфига
			$file = $key;
			$key = NULL;
		}
		else {
			// Есть и файл, и ключ
			$file = substr($key, 0, $pos);
			$key = substr($key, $pos + 1);
		}

		return [$file, $key];
	}

	/*
		Взять массив конфига из кэша
		Если еще нет в кэше - загрузить и поместить в кэш
		Арг $forget = TRUE - не сохранять весь конфиг в буфере,
			действует только при первом взятии всего конфига
	!!!	Разные комбинации alias+file могут давать один и тот же файл,
	!!!	поэтому ключом кэша м.б. только полное имя файла
	*/
	protected static function load($file)
	{
		// Взять из кэша, если нет - загрузить
		if (isset(static::$items[$file])) {
			return static::$items[$file];
		}

		// Файла в кэше еще нет - загрузить в кэш
		$path = static::dir().$file.'.php';
		if (! file_exists($path)) {
		//	throw new \Exception("Can't find config file `{$path}`.");
			return [];
		}
		$config = include $path;

		// Сохранять в кэше
		return static::$items[$file] = $config;
	}
}
