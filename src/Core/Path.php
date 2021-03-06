<?php
/*
	PathFile - хранилище алиасов путей к директориям сервера

!!!	Некорректный путь алиаса вызывает трудно отлавливаемые наведеннные ошибки
!!!	Поэтому методы этого класса (file() & dir()) генерят исключения для некорректного пути
*/
namespace Micro\Core;

class Path
{
	// Ассоциативный массив путей: 'alias' => 'abs_path'
	protected $aliases = [];

	/**
	 * Construct new instance
	 * 
	 * @param  array	$map	Associative data array
	 * @return void
	 */
	public function __construct($map = [])
	{
		foreach ($map as $key => $val) {
			$this->set($key, $val);
		}
	}

	/*
		Установить/Вернуть массив алиасов
	*/
	public function aliases($aliases = NULL)
	{
		if (! is_null($aliases)) {
			$this->aliases = $aliases;
		}

		return $this->aliases;
	}

	/*
		Есть ли алиас?
	*/
	public function has($alias)
	{
		return isset($this->aliases[$alias]);
	}

	/*
		Вернуть абс. путь алиаса
	!!!	Путь НЕ нормализован и не проверен на существование - см. file() | dir() !!!
		Возвращает FALSE, если алиас не определен
	*/
	public function get($alias, $file = NULL)
	{
		if (! isset($this->aliases[$alias])) {
			return FALSE;
		}

		$path = $this->aliases[$alias];

		return $file ? $path.DIRECTORY_SEPARATOR.$file : $path;
	}

	/*
		Вернуть абс.путь заданного файла в пути заданного алиаса
		Путь нормализован и проверен на существование
	!!!	Генерит исключение при некорректностях !!!
	*/
	public function file($alias, $file)
	{
		$path = $this->get($alias, $file);

		if (($real = realpath($path)) === FALSE) {
			// Чего-то не так - разберемся, чего именно...
			if (! isset($this->aliases[$alias])) {
				throw new \Exception("Alias `{$alias}` is not defined");
			}
			else if (realpath($alias_path = $this->aliases[$alias]) === FALSE) {
				throw new \Exception("Can't find alias `{$alias}` path `{$alias_path}`");
			}
			else {
				throw new \Exception("Can't find path|file `{$file}` in the alias `{$alias}` path `{$path}`");
			}
		}

		return $real;
	}

	/*
		Вернуть абс.путь заданного директория в пути заданного алиаса
		Путь нормализован, проверен на существование и завершается DIRECTORY_SEPARATOR
	!!!	Генерит исключение при некорректностях !!!
	*/
	public function dir($alias, $dir)
	{
		return $this->file($alias, $dir).DIRECTORY_SEPARATOR;
	}

	/*
		Регистрировать новый алиас пути
		Путь может быть задан относит. другого алиаса в массиве
	!!!	Возвращает FALSE для некорректного пути

		Пример:
			Path::set('docroot', 'c:/www/my/site'));		// Abs path				=> 'c:/www/my/site'
			Path::set('vendor', ['docroot', '']);			// Rel path, full form	=> 'c:/www/my/site'
			Path::set('vendor', ['docroot', 'vendor']);		// Rel path, full form	=> 'c:/www/my/site/vendor'
			Path::set('vendor', ['docroot', '*']);			// Rel path, spec form	=> 'c:/www/my/site/vendor'
			Path::set('vendor', ['docroot']);				// Rel path, short form	=> 'c:/www/my/site/vendor'
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

	/*
		Определить карту (массив) алиасов
		Пример:
			Path::set(array(
				'docdata'	=> 'laravel',
				'lara'		=> array('docdata', 'laravel'),
				'bundle'	=> array('lara', 'bundles'),
			));
	*/
	public function map($map = NULL)
	{
		// Установить карту
		foreach ($map as $key => $val) {
			$this->set($key, $val);
		}

		return;
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
