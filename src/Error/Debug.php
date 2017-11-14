<?php
namespace Micro\Error;

/**
 * Contains debugging and dumping tools from Kohana
 */
class Debug
{
	// Debug charset
	public static $charset = 'UTF-8';

	//	PHP error code => human readable name
	public static $php_errors = [
		E_ERROR				=> 'Fatal Error',
		E_USER_ERROR		=> 'User Error',
		E_PARSE				=> 'Parse Error',
		E_WARNING			=> 'Warning',
		E_USER_WARNING		=> 'User Warning',
		E_STRICT			=> 'Strict',
		E_NOTICE			=> 'Notice',
		E_RECOVERABLE_ERROR	=> 'Recoverable Error',
		E_DEPRECATED		=> 'Deprecated',
	];

	// error view
	public static $view = ['file' => 'errorview.php', 'data' => []];

	// Path aliases for static::path() - defind outside (Path::$aliases)
	public static $path_aliases;

	/**
	 * Make a response View object representing the exception
	 *
	 * @uses    Kohana_Exception::text
	 * @param   Exception  $e
	 * @return  Response
	 */
	public static function view(\Exception $e)
	{
		// Установить алиасы путей файлов для удобочитаемости
		if (is_null(static::path_aliases())) {
			// Set min path aliases if not set
			static::path_aliases([
				'DOCROOT'	=> realpath($_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI']),
			]);
		}

		// XDebug trace if defined
		$e->tracer = static::ext_trace($e) ?: $e->getTrace();

		// Use the human-readable error name
		$code = $e->getCode();
		$e->desc = isset(static::$php_errors[$code]) ? static::$php_errors[$code] : (string) $code;

		// Prepare exception step file names
		$trace = static::trace($e->tracer);

		// Prepare PHP super-globals
		$php_all_vars = [ '_SERVER', '_ENV', '_GET', '_POST', '_REQUEST', '_FILES', '_COOKIE', '_SESSION' ];
		$php_vars = [];

		foreach ($php_all_vars as $name) {
			if (isset($GLOBALS[$name])) {
				foreach ($GLOBALS[$name] as $key => $value) {
					$php_vars['$'.$name][$key] = static::dump($value);
				}
			}
		}

		// Prepare included files list
		$included = get_included_files();
		foreach ($included as $key => $path) {
			$included[$key] = static::path($path);
		}

		// Prepare declared classes list
		$classes = static::get_declared_classes();
		foreach ($classes as $key => $path) {
			$classes[$key] = static::path($path);
		}

		// Define vars for exception view
		static::$view['data'] = [
			'e'			=> $e,
			'file'		=> static::path($e->getFile()),
			'source'	=> static::source($e->getFile(), $e->getLine()),
			'trace'		=> $trace,
			'php_vars'	=> $php_vars,
			'included'	=> $included,
			'classes'	=> $classes,
			'extensions'=> get_loaded_extensions(),
		];

		return static::render_view();
	}

	public static function render_view()
	{
		// Clean the output buffer if one exists - previous echo's could be lost
	//	ob_get_level() AND ob_clean();

		// Try to make the response output using exception template
		ob_start();

		extract(static::$view['data']);
		require static::$view['file'];

		return ob_get_clean();
	}

	/*
		Get extended trace (XDebug)
	*/
	public static function ext_trace($e)
	{
		$trace = NULL;

		if ($e instanceof \ErrorException)
		{
			// If XDebug is installed, and this is a fatal error,
			// use XDebug to generate the stack trace
			if (function_exists('xdebug_get_function_stack') AND $e->getCode() == E_ERROR)
			{
				$trace = array_slice(array_reverse(xdebug_get_function_stack()), 4);
				foreach ($trace as & $frame)
				{
					// XDebug pre 2.1.1 doesn't currently set the call type key
					// http://bugs.xdebug.org/view.php?id=695
					if (isset($frame['type'])) {
						if ($frame['type'] == 'dynamic')	$frame['type'] = '->';
						elseif ($frame['type'] == 'static')	$frame['type'] = '::';
						else								$frame['type'] = '??';
					}
					else {
						$frame['type'] = '--';
					}

					// XDebug also has a different name for the parameters array
					if (isset($frame['params']) AND ! isset($frame['args']))
					{
						$frame['args'] = $frame['params'];
					}
				}
			}
		}

		// The stack trace becomes unmanageable inside PHPUnit.
		// The error view ends up several GB in size, taking serveral minutes to render.
		if (defined('PHPUnit_MAIN_METHOD')) {
			$trace = array_slice($trace, 0, 2);
		}

		return $trace;
	}

	public static function chars($row)
	{
		return htmlspecialchars($row, ENT_NOQUOTES, static::$charset);
	}

	/**
	 * Returns an HTML string of debugging information about any number of
	 * variables, each wrapped in a "pre" tag:
	 *
	 *     // Displays the type and value of each variable
	 *     echo Debug::vars($foo, $bar, $baz);
	 *
	 * @param   mixed   $var,...    variable to debug
	 * @return  string
	 */
	public static function vars()
	{
		if (func_num_args() === 0)
			return;

		// Get all passed variables
		$variables = func_get_args();

		$output = array();
		foreach ($variables as $var)
		{
			$output[] = static::_dump($var, 1024);
		}

		return '<pre class="debug">'.implode("\n", $output).'</pre>';
	}

	// Супер-дамп - ErrorDebug
	public static function dump_styled($var, $length = 512, $level = 5)
	{
		static $style = '
			<meta charset="UTF-8">
			<style type="text/css">
				.dump_var {font-size:12px;}
				.dump_var .string {color:red;}
				.dump_var .integer {color:green;}
				.dump_var .key_str {color:darkred;}
				.dump_var .key_int {color:darkgreen;}
			</style>';

		$dump = $style.'<pre class="dump_var">'.static::_dump($var, $length, $level).'</pre>';

		// Один лишь раз сады цветут...
		if ($style) $style = '';

		return $dump;
	}

	/**
	 * Returns an HTML string of information about a single variable.
	 *
	 * Borrows heavily on concepts from the Debug class of [Nette](http://nettephp.com/).
	 *
	 * @param   mixed   $value              variable to dump
	 * @param   integer $length             maximum length of strings
	 * @param   integer $level_recursion    recursion limit
	 * @return  string
	 */
	public static function dump($value, $length = 128, $level_recursion = 10)
	{
		return static::_dump($value, $length, $level_recursion);
	}

	/**
	 * Helper for Debug::dump(), handles recursion in arrays and objects.
	 *
	 * @param   mixed   $var    variable to dump
	 * @param   integer $length maximum length of strings
	 * @param   integer $limit  recursion limit
	 * @param   integer $level  current recursion level (internal usage only!)
	 * @return  string
	 * TODO: стилизовать вывод dump()
	 */
	protected static function _dump(&$var, $length = 128, $limit = 10, $level = 0)
	{
		if ($var === NULL)
		{
			return '<small>NULL</small>';
		}
		elseif (is_bool($var))
		{
			return '<small>bool</small> '.($var ? 'TRUE' : 'FALSE');
		}
		elseif (is_float($var))
		{
			return '<small>float</small> '.$var;
		}
		elseif (is_resource($var))
		{
			if (($type = get_resource_type($var)) === 'stream' AND $meta = stream_get_meta_data($var))
			{
				$meta = stream_get_meta_data($var);

				if (isset($meta['uri']))
				{
					$file = $meta['uri'];

					if (function_exists('stream_is_local'))
					{
						// Only exists on PHP >= 5.2.4
						if (stream_is_local($file))
						{
							$file = static::path($file);
						}
					}

					return '<small>resource</small><span>('.$type.')</span> '.static::chars($file);
				}
			}
			else
			{
				return '<small>resource</small><span>('.$type.')</span>';
			}
		}
		elseif (is_string($var))
		{
/*
			// Clean invalid multibyte characters. iconv is only invoked
			// if there are non ASCII characters in the string, so this
			// isn't too much of a hit.
			$var = UTF8::clean($var, Kohana::$charset);

			if (UTF8::strlen($var) > $length)
			{
				// Encode the truncated string
				$str = htmlspecialchars(UTF8::substr($var, 0, $length), ENT_NOQUOTES, Kohana::$charset).'&nbsp;&hellip;';
			}
			else
			{
				// Encode the string
				$str = htmlspecialchars($var, ENT_NOQUOTES, Kohana::$charset);
			}
*/
			return '<small>string</small><span>('.strlen($var).')</span> "<span class="string">'.static::chars($var).'</span>"';
		}
		elseif (is_array($var))
		{
			$output = array();

			// Indentation for this variable
			$space = str_repeat($s = '    ', $level);

			static $marker;

			if ($marker === NULL)
			{
				// Make a unique marker
				$marker = uniqid("\x00");
			}

			if (empty($var))
			{
				// Do nothing
			}
			elseif (isset($var[$marker]))
			{
				$output[] = "(\n$space$s*RECURSION*\n$space)";
			}
			elseif ($level < $limit)
			{
				$output[] = "<span>(";

				$var[$marker] = TRUE;
				foreach ($var as $key => & $val)
				{
					if ($key === $marker) continue;
					if ( ! is_int($key))
					{
						//$key = '"'.static::chars($key).'"';
						$key = '"<span class="key_str">'.static::chars($key).'</span>"';
					}
					else {
						$key = '<span class="key_int">'.$key.'</span>';
					}

					$output[] = "$space$s$key => ".static::_dump($val, $length, $limit, $level + 1);
				}
				unset($var[$marker]);

				$output[] = "$space)</span>";
			}
			else
			{
				// Depth too great
				$output[] = "(\n$space$s...\n$space)";
			}

			return '<small>array</small><span>('.count($var).')</span> '.implode("\n", $output);
		}
		elseif (is_object($var))
		{
			// Copy the object as an array
			$array = (array) $var;

			$output = array();

			// Indentation for this variable
			$space = str_repeat($s = '    ', $level);

			$hash = spl_object_hash($var);

			// Objects that are being dumped
			static $objects = array();

			if (empty($var))
			{
				// Do nothing
			}
			elseif (isset($objects[$hash]))
			{
				$output[] = "{\n$space$s*RECURSION*\n$space}";
			}
			elseif ($level < $limit)
			{
				$output[] = "<code>{";

				$objects[$hash] = TRUE;
				foreach ($array as $key => & $val)
				{
					if ($key[0] === "\x00")
					{
						// Determine if the access is protected or protected
						$access = '<small>'.(($key[1] === '*') ? 'protected' : 'private').'</small>';

						// Remove the access level from the variable name
						$key = substr($key, strrpos($key, "\x00") + 1);
					}
					else
					{
						$access = '<small>public</small>';
					}

					$output[] = "$space$s$access $key => ".static::_dump($val, $length, $limit, $level + 1);
				}
				unset($objects[$hash]);

				$output[] = "$space}</code>";
			}
			else
			{
				// Depth too great
				$output[] = "{\n$space$s...\n$space}";
			}

			return '<small>object</small> <span>'.get_class($var).'('.count($array).')</span> '.implode("\n", $output);
		}
		else
		{
			if (gettype($var) == 'integer') {
 				return '<small>'.gettype($var).'</small> <span class="integer">'.static::chars(print_r($var, TRUE)).'</span>';
			}
 			return '<small>'.gettype($var).'</small> '.static::chars(print_r($var, TRUE));
		}
	}

	/**
	 * Set or get path aliases
	 *
	 * @param   array  $aliases   array [ aliase => path ]
	 * @return  string
	 */
	public static function path_aliases($aliases = NULL)
	{
		if (! is_null($aliases)) {			// set aliases
			// Reverse sort aliases array to be shure longer paths be the first to found
			arsort($aliases);

			// set aliases
			static::$path_aliases = $aliases;

		}

		// return aliases
		return static::$path_aliases;
	}

	/**
	 * Removes application, system, modpath, or docroot from a filename,
	 * replacing them with the plain text equivalents. Useful for debugging
	 * when you want to display a shorter path.
	 *
	 *     // Displays SYSPATH/classes/kohana.php
	 *     echo Debug::path(Kohana::find_file('classes', 'kohana'));
	 *
	 * @param   string  $file   path to debug
	 * @return  string
	 */
	public static function path($file)
	{
		// Берем все зарегистрированные алиасы путей
		$aliases = static::path_aliases();

		// Меняем в имени файла путь на алиас
		foreach ($aliases as $alias => $path) {
			if (strpos($file, $path) === 0) {
				return '<strong>'.strtoupper($alias).'</strong>'.substr($file, strlen($path));
			}
		}

		// Ничего не подошло - возвращаем исходный путь
		return $file;
	}

	/**
	 * Returns an HTML string, highlighting a specific line of a file, with some
	 * number of lines padded above and below.
	 *
	 *     // Highlights the current line of the current file
	 *     echo Debug::source(__FILE__, __LINE__);
	 *
	 * @param   string  $file           file to open
	 * @param   integer $line_number    line number to highlight
	 * @param   integer $padding        number of padding lines
	 * @return  string   source of file
	 * @return  FALSE    file is unreadable
	 */
	public static function source($file, $line_number, $padding = 5)
	{
		if ( ! $file OR ! is_readable($file))
		{
			// Continuing will cause errors
			return FALSE;
		}

		// Open the file and set the line position
		$file = fopen($file, 'r');
		$line = 0;

		// Set the reading range
		$range = array('start' => $line_number - $padding, 'end' => $line_number + $padding);

		// Set the zero-padding amount for line numbers
		$format = '% '.strlen($range['end']).'d';

		$source = '';
		while (($row = fgets($file)) !== FALSE)
		{
			// Increment the line number
			if (++$line > $range['end'])
				break;

			if ($line >= $range['start'])
			{
				// Make the row safe for output
				$row = static::chars($row);

				// Trim whitespace and sanitize the row
				$row = '<span class="number">'.sprintf($format, $line).'</span> '.$row;

				if ($line === $line_number)
				{
					// Apply highlighting to this row
					$row = '<span class="line highlight">'.$row.'</span>';
				}
				else
				{
					$row = '<span class="line">'.$row.'</span>';
				}

				// Add to the captured source
				$source .= $row;
			}
		}

		// Close the file
		fclose($file);

		return '<pre class="source"><code>'.$source.'</code></pre>';
	}

	/**
	 * Returns an array of HTML strings that represent each step in the backtrace.
	 *
	 *     // Displays the entire current backtrace
	 *     echo implode('<br/>', Debug::trace());
	 *
	 * @param   array   $trace
	 * @return  string
	 */
	public static function trace(array $trace = NULL)
	{
		if ($trace === NULL)
		{
			// Start a new trace
			$trace = debug_backtrace();
		}

		// Non-standard function calls
		$statements = array('include', 'include_once', 'require', 'require_once');

		$output = array();
		foreach ($trace as $step)
		{
			if ( ! isset($step['function']))
			{
				// Invalid trace step
				continue;
			}

			if (isset($step['file']) AND isset($step['line']))
			{
				// Include the source of this step
				$source = static::source($step['file'], $step['line']);
			}

			if (isset($step['file']))
			{
				$file = static::path($step['file']);

				if (isset($step['line']))
				{
					$line = $step['line'];
				}
			}

			// function()
			$function = $step['function'];

			if (in_array($step['function'], $statements))
			{
				if (empty($step['args']))
				{
					// No arguments
					$args = array();
				}
				else
				{
					// Sanitize the file path
					$args = array($step['args'][0]);
				}
			}
			elseif (isset($step['args']))
			{
				if ( ! function_exists($step['function']) OR strpos($step['function'], '{closure}') !== FALSE)
				{
					// Introspection on closures or language constructs in a stack trace is impossible
					$params = NULL;
				}
				else
				{
					if (isset($step['class']))
					{
						if (method_exists($step['class'], $step['function']))
						{
							$reflection = new \ReflectionMethod($step['class'], $step['function']);
						}
						else
						{
							$reflection = new \ReflectionMethod($step['class'], $step['type'] === '->' ? '__call' : '__callStatic');
						}
					}
					else
					{
						$reflection = new \ReflectionFunction($step['function']);
					}

					// Get the function parameters
					$params = $reflection->getParameters();
				}

				$args = array();

				foreach ($step['args'] as $i => $arg)
				{
					$arg = static::dump($arg);

					if (isset($params[$i]))
					{
						// Assign the argument by the parameter name
						$args[$params[$i]->name] = $arg;
					}
					else
					{
						// Assign the argument by number
						$args[$i] = $arg;
					}
				}
			}

			if (isset($step['class']))
			{
				// Class->method() or Class::method()
				$function = $step['class'].$step['type'].$step['function'];
			}

			$output[] = array(
				'function' => $function,
				'args'     => isset($args)   ? $args : NULL,
				'file'     => isset($file)   ? $file : NULL,
				'line'     => isset($line)   ? $line : NULL,
				'source'   => isset($source) ? $source : NULL,
			);

			unset($function, $args, $file, $line, $source);
		}

		return $output;
	}

	//=============================================================================
	//	Декларированные классы
	//	Список начинается с длинной простыни классов PHP, он прекращается неизвестно на каком
	//=============================================================================

	// Если задан, означает первый класс приложения, все предшествующие отсекаем
	//public static $first_class = 'Path';

	public static function get_declared_classes($first_class = NULL)
	{
		$classes = get_declared_classes();
		//$first = $first_class ?: static::$first_class;
		$first = $first_class ?: static::first_namespaced_class($classes);

		// Ищем в списке первый пользовательский класс
		if ($first AND ($key = array_search($first, $classes)) !== FALSE)
		{
			// нашли - убираем системные классы
			$classes = array_slice($classes, $key);
		}

		// Пометить в простыне имена вендоров - первые сегменты имен
		return static::mark_vendor_namespace($classes);
	}

	// Найти в простыне первый класс с namespace
	public static function first_namespaced_class($classes = NULL)
	{
		foreach ($classes as $class) {
			if (($pos = strpos($class, '\\')) !== false) {
				// Найден первый класс со своим пространством - возвращаем его полное имя
				return $class;
			}
		}
	}

	// Пометить в простыне имена вендоров - первые сегменты имен
	public static function mark_vendor_namespace(array $classes)
	{
		$vendors = array();

		foreach ($classes as $class) {
			if (($pos = strpos($class, '\\')) !== false) {
				// Это класс со своим пространством - выделяем первый сегмент его имени
				$vendors[] = '<strong>'.substr($class, 0, $pos).'</strong>'.substr($class, $pos);
			}
			else {
				$vendors[] = $class;
			}
		}

		return $vendors;
	}
}
