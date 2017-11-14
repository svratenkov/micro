<?php
/*
	ConfigManager - массивы конфигурации с точечной нотацией
	-	Файлы конфига сидят в одном дире, заданным переменной dir
	-	Доступ к элементу конфига по ключу c двумя секциями и с точеченой нотацией:
			<key>	::= <file>[<item>]
				<file>	::= {dir/}file
				<item>	::=	{.segment}
	-	Добавлены "переменные" на плэйсхолдерах ':key' и их подстановка в строках и массивах
*/
namespace Micro\Core;

class ConfigManager
{
	// Кэш загруженных массивов (!!!не файлов!!!) конфига
	public $items = array();

	// Базовый директорий файлов конфига для этого объекта
	public $dir;

	// Базовый директорий файлов конфига для этого объекта
	public static $path = 'dir';

	/*
		Конструктор - определить базовый дир конфигов этого объекта
	*/
	public function __construct($dir)
	{
		if (($path = realpath($dir)) === FALSE) {
			throw new \Exception("Can't find config directory `{$dir}`.");
		}
		$this->dir = $path.DIRECTORY_SEPARATOR;
	}

	/*
		Есть элемент конфига?
	*/
	public function has($key)
	{
		// Разбор ключа - массив элемента конфига и ключ элемента
		list($items, $item) = $this->items($key);

		return isset($items[$item]);
	}

	/*
		Вернуть значение элемента конфига, если его нет - $default
		Если задан массив подстановок, заменить плэйсхолдеры ':key' на заданные значения
	*/
	public function get($key, $default = NULL, $replacements = NULL)
	{
		// ключ не задан, вернуть весь массив КОНФИГОВ
		if ($key) {
			// Разбор ключа - массив элемента конфига и ключ элемента
			list($items, $item) = $this->items($key);

			// Есть ключ элемента с точечной нотацией
			$items = $this->array_get($items, $item, $default);
		}
		else {
			$items = $this->items;
		}

		if ($replacements) {
			$items = $this->array_replace($items, array_keys($replacements), array_values($replacements));
		}

		return $items;
	}

	/*
		Установить значение элемента конфига
		если ключ не задан, установить весь массив элементов
	*/
	public function set($key, $value)
	{
		if ($key) {
			// Разбор ключа - массив элемента конфига и ключ элемента
			list($items, $item, $file) = $this->items($key);

			// Есть ключ элемента с точечной нотацией
			$this->items[$file] = $this->array_set($items, $item, $value);
		}
		else {
			// ключ не задан, установить новый массив КОНФИГОВ
			$this->items = $value;
		}

	//	return $value;
	}

	public static function array_get($array, $key, $default = NULL)
	{
        if (is_null($key)) {
            return $array;
        }

		if ( ! is_array($array)) {
			return $default;
		}

        if (isset($array[$key])) {
            return $array[$key];
        }

		foreach (explode('.', $key) as $segment)
		{
			if (! isset($array[$segment]) or ! array_key_exists($segment, $array)) {
				return $default;
			}
			$array = $array[$segment];
		}

		return $array;
	}

	public static function array_set(&$array, $key, $value)
	{
		if (is_null($key)) {
			return $array = $value;
		}

		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				$array[$key] = array();
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}

	/*
		Замена всех подстановок в заданном массиве
	*/
	public function array_replace($array, $search, $replace)
	{
		// Json - элегантный И БЫСТРЫЙ способ, но с проблемами представления строк в JScript
	//	return (array) json_decode(str_replace($search, $replace, json_encode($array))); 

		// Рекурсия - просто и надежно
		$new = array();

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$new[$key] = $this->array_replace($value, $search, $replace);
			}
			else if (is_string($value)) {
				$new[$key] = str_replace($search, $replace, $value);
			}
			else {
				$new[$key] = $value;
			}
		}

		return $new;
	}

	/*
		Вернуть массив конфига и сегменты для заданного ключа
	*/
	public function items($key)
	{
		if (! $key) {
			return [[], ''];
		}

		// Разбор ключа - путь к файлу конфига и ключ элемента
		list($file, $item) = $this->parse_key($key);

		// Взять массив конфига из кэша
		$items = $this->load($file);

		return [$items, $item, $file];
	}

	/*
		Парсер ключа эл-та конфига
		Возвращает массив из двух секций
	*/
	protected function parse_key($key)
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
	protected function load($file)
	{
		// Взять из кэша, если нет - загрузить
		if (isset($this->items[$file])) {
			return $this->items[$file];
		}

		// Файла в кэше еще нет - загрузить в кэш
		$path = $this->dir.$file.'.php';
		if (! file_exists($path)) {
			throw new \Exception("Can't find config file `{$file}`.");
		}
		$config = include $path;

		// Сохранять в кэше
		return $this->items[$file] = $config;
	}
}
