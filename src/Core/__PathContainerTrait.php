<?php
/*
	FrontController - app level container

	Responsibilities:
	- Routes HTTP request
	- Creates IoC Container common for all MVC components
	- Handles PageController for corresponding view & model
	- Performs HTTP respones

	ASP.NET Front Controller (https://msdn.microsoft.com/ru-ru/library/ms978723.aspx)
		The handler has two responsibilities: 
		- Retrieve parameters.
			The handler receives the HTTP Post or Get request from the Web server
			and retrieves relevant parameters from the request. 
		- Select commands.
			The handler uses the parameters from the request first to choose the correct command
			and then to transfer control to the command for processing.
*/
namespace Micro\Core;
use Micro\Container\IocContainer;


trait PathContainerTrait // extends IocContainer
{
//	const KEY_PATH = 'path';

	/**
	 * Construct new container instance
	 * Creates path data branch
	 * 
	 * @param  array	$data	Associative data array
	 * @return void
	 */
	public function __construct($data = [])
	{
		parent::set($this::KEY_PATH, []);
		parent::add($data);
	}

	//=============================================================================
	//	Path handling
	//=============================================================================

	/*
		Return path aliases array
	*/
	public function pathAliases()
	{
		return $this->get($this::KEY_PATH);
	}

	/*
		Есть ли алиас?
	*/
	public function pathHas($alias)
	{
		return isset($this->pathAliases()[$alias]);
	}

	/*
		Вернуть абс. путь алиаса
		Возвращает FALSE, если алиас не определен
	*/
	public function pathGet($alias)
	{
		return isset($this->pathAliases()[$alias]) ? $this->pathAliases()[$alias] : FALSE;
	}

	/*
		Вернуть абс.путь заданного файла в пути заданного алиаса
		Путь нормализован и проверен на существование
	!!!	Генерит исключение при некорректностях !!!
	*/
	public function pathFile($alias, $file)
	{
		$path = $this->pathGet($alias).DIRECTORY_SEPARATOR.$file;

		if (($real = realpath($path)) === FALSE) {
			// Чего-то не так - разберемся, чего именно...
			if (! $this->pathHas($alias)) {
				throw new \Exception(__METHOD__."(): Path alias `{$alias}` is not defined");
			}
			else if (realpath($alias_path = $this->pathGet($alias)) === FALSE) {
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
	public function pathDir($alias, $dir)
	{
		return $this->pathFile($alias, $dir).DIRECTORY_SEPARATOR;
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
	public function pathSet($alias, $path = NULL)
	{
		// Установить карту алиасов, если задана
		if (is_array($alias)) {
			foreach ($alias as $key => $val) {
				$this->pathSet($key, $val);
			}
			return;
		}

		// Обработать спец синтакс пути алиаса
		if (is_array($path)) {
			// Путь задан относит. некоторого другого алиаса
			if (! isset($path[1]) or $path[1] == '*') {
				// краткая или спец форма - путем является алиас
				$path[1] = $alias;
			}

			if (($path = $this->pathFile($path[0], $path[1])) === FALSE) {
				return FALSE;
			}
		}

		// Установить алиас и путь (Установить ветку алиасов путей в контейнере, есди ее еще нет)
		$aliases = $this->get($this::KEY_PATH);
		$aliases[$alias] = $path;
		$this->set($this::KEY_PATH, $aliases);
	}
}
