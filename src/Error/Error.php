<?php
/*
	Обработка ошибок и исключений
	Использует красивый трекер Kohana
*/
namespace Micro\Error;

class Error
{
	const PHP_VERSION_MIN = '5.5';

	// Exception handler params
//	public static $level	= -1;		// Error reporting level: 0 - none, -1 - all
	public static $charset	= 'UTF-8';	// Exceptions charset
	public static $debug	= TRUE;		// use debug template
	public static $logger;				// = function($exception) { Log::exception($exception); }

	/*
		Установить все ядреные обработчики ошибок
	*/
	public static function register($level = -1, $debug = TRUE, $logger = NULL, $charset = 'UTF-8')
	{
		if (version_compare(PHP_VERSION, static::PHP_VERSION_MIN) < 0) {
		    die("Requires PHP {static::PHP_VERSION_MIN} or above");
		}

		// PHP Display Errors Configuration
	//	ini_set('display_errors', 1);

		// Установить конфигурацию
		static::level($level);
		static::charset($charset);
		static::logger($logger);
		static::debug($debug);

		// Установить обработчики
		set_exception_handler([static::class, 'exception_handler']);
		set_error_handler([static::class, 'error_handler']);
		register_shutdown_function([static::class, 'shutdown_handler']);
	}

	/*
		Установить/вернуть уровень ошибок
	*/
	public static function level($level = NULL)
	{
		if (! is_null($level)) {
			error_reporting($level);
		}
	//	return static::$level = error_reporting();
	}

	/*
		Установить/вернуть кодировку
	*/
	public static function charset($charset = NULL)
	{
		if (! is_null($charset)) {
			static::$charset = $charset;
		}

		return static::$charset;
	}

	/*
		Установить/вернуть режим отладки: красивые дамп переменных и ошибок Коханы
	*/
	public static function debug($debug = NULL)
	{
		if ($debug and ! class_exists(Debug::class, FALSE)) {
			// Ручная загрузка доп классов
			require __DIR__.'/Debug.php';			// красивый дамп ошибок Коханы
			require __DIR__.'/vardump.php';			// хелперы улучшенного дампа переменных --> helpers.php
		}

		return static::$debug = (bool) $debug;
	}

	/*
		Установить/вернуть логгер ошибок
	*/
	public static function logger($logger = NULL)
	{
		if (! is_null($logger)) {
			static::$logger = $logger;
		}

		return static::$logger;
	}

	/**
	 * PHP error handler, converts all errors into ErrorExceptions. This handler
	 * respects error_reporting settings.
	 *
	 * @throws  ErrorException
	 * @return  TRUE
	 */
	public static function error_handler($code, $msg, $file = NULL, $line = NULL)
	{
		if (error_reporting() & $code)
		{
			// This error is not suppressed by current error reporting settings
			// Convert the error into an ErrorException
		//	throw new \ErrorException($msg, $code, $code, $file, $line);

			// Fake an exception for nice debugging
			static::exception_handler(new \ErrorException($msg, $code, $code, $file, $line));

			// Shutdown now to avoid a "death loop"
			exit(1);
		}

		// Do not execute the PHP error handler
		return TRUE;
	}

	/**
	 * Handle the PHP shutdown event.
	 * Catches errors that are not caught by the error handler, such as E_PARSE.
	 *
	 * @return void
	 */
	public static function shutdown_handler()
	{
		// If a fatal error occurred that we have not handled yet, we will
		// create an ErrorException and feed it to the exception handler,
		// as it will not yet have been handled.
		$error = error_get_last();

		if ( ! is_null($error))
		{
			// Clean the output buffer: echo(), var_dump(), ...
			//ob_get_level() AND ob_clean();

			extract($error, EXTR_SKIP);

			// Fake an exception for nice debugging
			static::exception_handler(new \ErrorException($message, $type, 0, $file, $line));

			// Shutdown now to avoid a "death loop"
			exit(1);
		}
	}

	/**
	 * Exception handler, logs the exception and displays the:
	 *		error message,
	 *		source of the exception,
	 *		the stack trace of the error.
	 *
	 * @uses    Kohana_Exception::response
	 * @param   Exception  $e
	 * @return  boolean
	 */
	public static function exception_handler(\Exception $e)
	{
		try
		{
			// Log the exception
			if (! is_null(static::$logger)) {
				call_user_func(static::$logger, $e);
			}

			// If detailed errors are enabled, we'll just format the exception into
			// a simple error message and display it on the screen. We don't use a
			// View in case the problem is in the View class.
			if (static::$debug and class_exists(Debug::class, TRUE))
			{
				// Try to make the response output using exception template
				try
				{
					$response = Debug::view($e);
				}
				catch (\Exception $e)
				{
					/**
					 * Things are going badly for us,
					 * the problem within the View class
					 * Lets try to keep things under control by generating a simpler response object.
					 */
					$response = static::text($e);
				}
			}
			else
			{
				$response = static::text($e);
			}
		}
		catch (\Exception $e)
		{
			/**
			 * Things are going *really* badly for us,
			 * the problem within the Log or Response class
			 * We now have no choice but to bail. Hard.
			 */
			// Clean the output buffer if one exists
		//	ob_get_level() AND ob_clean();

			// Set the Status code to 500, and Content-Type to text/plain.
			header('Content-Type: text/plain; charset='.static::$charset, TRUE, 500);
			echo static::text($e);
			exit(1);
		}

		// Send the response to the browser
		echo $response;
		exit(1);
	}

	/*
		Get a single line of text representing the exception:
		Error [ Code ]: Message ~ File [ Line ]
	*/
	public static function text(\Exception $e)
	{
		// Если можно - красивые пути файлов
	//	$file = $e->getFile();
	//	if (class_exists(ErrorDebug::class, FALSE)) {
	//		$file = ErrorDebug::path($file);
	//	}

	//	return sprintf('%s [ %s ]: %s ~ %s [ %d ]', get_class($e), $e->getCode(), strip_tags($e->getMessage()), $e->getFile(), $e->getLine());
		return '<pre>'.$e->__toString().'</pre>';
	}
}
