<?php
/*
	PathAlias - хранилище алиасов путей к любым "крамбам" - файлам, директориям, URL,...
	Базовый класс для хранилищ системных путей и урлов.
	Никаких проверок корректности путей не производится.
*/
namespace Micro\Path;

class PathAlias
{
	// Ассоциативный массив путей: 'alias' => 'abs_path'
	public $aliases;

	// Разделитель путей
	public $separator = '/';

	/**
	 * Конструктор
	 * 
	 * @param  array  $aliases
	 * @return void
	 */
	public function __construct($aliases = [])
	{
		$this->aliases = $aliases;
	}

	/**
	 * Установить/Вернуть массив алиасов
	 * 
	 * @param  array  $aliases
	 * @return array
	 */
	public function aliases($aliases = NULL)
	{
		if (! is_null($aliases)) {
			$this->aliases = $aliases;
		}

		return $this->aliases;
	}

	/**
	 * Есть ли алиас?
	 * 
	 * @param  string $alias
	 * @return bool
	 */
	public function has($alias)
	{
		return isset($this->aliases[$alias]);
	}

	/**
	 * Вернуть абс. путь алиаса
	 * 
	 * @param  string $alias
	 * @param  string $file
	 * @return string
	 */
	public function get($alias, $file = NULL)
	{
		if (! isset($this->aliases[$alias])) {
			return FALSE;
		}

		$path = $this->aliases[$alias];

		return $file ? $path.$this->separator.$file : $path;
	}

	/**
	 * Регистрировать новый алиас пути
	 * Путь может быть задан относит. другого алиаса в массиве
	 * Возвращает FALSE для некорректного пути
	 * 
	 * Пример:
	 * 		Path::set('docroot', 'c:/www/my/site'));	// Abs path				=> 'c:/www/my/site'
	 * 		Path::set('vendor', ['docroot', 'vendor']);	// Rel path, full form	=> 'c:/www/my/site/vendor'
	 * 		Path::set('vendor', ['docroot', '*']);		// Rel path, spec form	=> 'c:/www/my/site/vendor'
	 * 		Path::set('vendor', ['docroot']);			// Rel path, short form	=> 'c:/www/my/site/vendor'
	 * 		Path::set('vendor', ['docroot', '']);		// Rel path, full form	=> 'c:/www/my/site'
	 * 
	 * @param  string $alias
	 * @param  string $path
	 * @return bool
	 */
	public function set($alias, $path)
	{
		if (is_array($path)) {
			// Путь задан относит. некоторого алиаса
			if (! isset($path[1]) or $path[1] == '*') {
				// краткая или спец форма - путем является алиас
				$path[1] = $alias;
			}

			if (($path = $this->get($path[0], $path[1])) === FALSE) {
				return FALSE;
			}
		}

		$this->aliases[$alias] = $path;
	}

	/**
	 * Определить карту алиасов
	 * Пример:
	 * 		Path::set(array(
	 * 			'docdata'	=> 'laravel',
	 * 			'lara'		=> array('docdata', 'laravel'),
	 * 			'bundle'	=> array('lara', 'bundles'),
	 * 		));
	 * 
	 * @param  array  $map
	 * @return void
	 */
	public function map($map = [])
	{
		// Установить карту
		foreach ($map as $key => $val) {
			$this->set($key, $val);
		}
	}

	/*
		Нормализация фрагментов '.', '..', '//', '\\' в заданном пути
		Нечто типа realpath(), но без перевода в абсолютный путь и проверок существования 
	public static function normalize($path)
	{
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
		$parts = explode(DIRECTORY_SEPARATOR, $path);
		$norms = array();

		foreach ($parts as $part) {
			if (empty($part) or '.' == $part) {
				 continue;
			}
			if ('..' == $part) {
				array_pop($norms);
			}
			else {
				$norms[] = $part;
			}
		}

		return implode(DIRECTORY_SEPARATOR, $norms);
    }
	*/
}
