<?php
/*
	Вызов системных команд
	Пример:
		Shell::exec('composer.bat dump-autoload -o')
		Shell::exec('dir')
*/
namespace Micro\Core;

class Shell
{
	// Origing cwd for temporary switching to shell command executions
	protected static $cwdOrigin;

	/*
		Вызвать системную команду и вернуть ее вывод STDOUT
		Возвращает массив строк вывода
		Арг $stderr перераправляет STDERR => STDOUT
	*/
	public static function exec($cmd, $cwd = NULL, $stderr = TRUE)
	{
		if ($cwd) {
			static::cwdChange($cwd);
		}

		exec($cmd.($stderr ? ' 2>&1' : ''), $rows);

		if ($cwd) {
			static::cwdRestore();
		}

		return $rows;
	}

	/*
		Вызвать системную команду и вернуть ее вывод STDOUT
		Возвращает вывод в виде одной строки
		Арг $stderr перераправляет STDERR => STDOUT
	*/
	public static function shell_exec($cmd, $stderr = TRUE)
	{
		$row = shell_exec($cmd.($stderr ? ' 2>&1' : ''));

		return $row;
	}

	/*
		Изменить текущий дир c сохранением прежнего
	*/
	public static function cwdChange($dir)
	{
		if (! is_dir($dir)) {
			throw new \Exception("Can't change dir to `{$dir}` - directory doesn't exist.");
		}

		static::$cwdOrigin = getcwd();
		return chdir($dir);
	}

	/*
		Восстановить текущий дир
	*/
	public static function cwdRestore()
	{
		return chdir(static::$cwdOrigin);
	}
}
