<?php
/*
	Хелперы работы с PHP кодами в файлах и строках
*/
namespace Micro\Core;

class Php
{
	// Буфер контентов всех файлов, прочитанных ранее с буферизацией: 'file_path' => 'file_content'
	public static $buffer = [];

	// Временное хранилище кода и данных для "чистой" компиляции
	protected static $clean = [];

	/*
		Хелпер: Взять контент файла из буфера
		Если файла нет в буфере - прочесть и все-таки взять
		Возвращает: контент файла из буфера
	*/
	public static function buffer($path)
	{
		// Если контента файла нет в буфере - читаем его и помещаем в буфер
		if (! isset(static::$buffer[$path])) {
			if (($content = file_get_contents($path)) === FALSE) {
				return NULL;
			}
			return static::$buffer[$path] = $content;
		}

		// Контент файла есть в буфере - возвращаем его
		return static::$buffer[$path];
	}

	/*
		Хелпер: компилирует и исполняет заданный код PHP в контексте заданных переменных
		Возвращает: РЕЗУЛЬТАТ ИСПОЛНЕНИЯ КОДА
		Годится для строк и массивов PHP кода в памяти
	*/
	public static function eval_code($code, $data = [])
	{
		static::$clean = [$code, $data];
		return static::eval_clean();
	}

	/*
		Хелпер: "Чистая" компиляция кода с данными в статических переменных
	*/
	protected static function eval_clean()
	{
		extract(static::$clean[1]);
		return eval('?>'.static::$clean[0]);
	}

	/*
		Хелпер: компилирует и исполняет заданный код PHP в контексте заданных переменных
		Возвращает: ВЫВОД КОДА В OUTPUT
		Годится для шаблонов в строках HTML/PHP кода
	*/
	public static function grab_code($code, $data = array())
	{
		ob_start();

		try {							// Load the view within the current scope
			static::eval_code($code, $data);
		}
		catch (\Exception $e) {
			ob_end_clean();				// Delete the output buffer
			throw $e;					// Re-throw the exception
		}
		
		return ob_get_clean();
	}

	/*
		Хелпер: грузит, компилирует и исполняет заданный файл PHP в контексте заданных переменных
		Возвращает: РЕЗУЛЬТАТ ИСПОЛНЕНИЯ КОДА
		Годится для строк и массивов PHP кода в файлах PHP
	*/
	public static function eval_file($path, $data = array(), $buffering = FALSE)
	{
		if ($buffering) {
			// Буферизация - берем контент из буфера и компилируем его
			return static::eval_code(static::buffer($path), $data);
		}
	
		// Единичная загрузка без буферизации - инклудим в контексте данных
		extract($data);
		return include $path;
	}

	/*
		Хелпер: грузит, компилирует и исполняет заданный файл PHP в контексте заданных переменных
		Возвращает: ВЫВОД КОДА В OUTPUT
		Годится для шаблонов в файлах HTML/PHP
	*/
	public static function grab_file($path, $data = array(), $buffering = FALSE)
	{
		ob_start();

		try {					// Load the view within the current scope
			static::eval_file($path, $data, $buffering);
		}
		catch (\Exception $e) {
			ob_end_clean();		// Delete the output buffer
			throw $e;			// Re-throw the exception
		}

		return ob_get_clean();
	}
}
