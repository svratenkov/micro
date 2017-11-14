<?php
/*
	Simplified Laravel\Blade

!!!	Все управляющие конструкции м.б. !!!! ТОЛЬКО В ОДНОЙ СТРОКЕ !!!!
		Проблема соответствия скобок (открывающие-закрывающие)
		если наложить условие: ОДНА КОНСТРУКЦИЯ <===> ОДНА СТРОКА КОДА
		Тогда открывающей будет первая скобка после ИД, закрывающей - последняя скобка в строке
		И можно пользовать просто RegEx вместо лексического компилятора
*/
namespace Micro\Core;
use Mvc\View\Renderers\PhpFileRenderer;

class Blade
{
	// Замены preg_replace() конструкций Blade на аналоги Php ПЕРЕД $str_replace
	public static $preg_before = [
		// Удаляемые (многострочные) комменты {{-- ... --}} - !!! с ними сбивается нумерация строк !!!
		'#{{--((.|\s)*?)--}}#'	=> '',
	];

	// Замены str_replace() конструкций Blade на аналоги Php
	public static $str_replace = [
		// Multi line comment - does not needed, are not included in the HTML
//		'[[--'			=> '<?php /*',
//		'--]]'			=> '*/? >',
		// Multi line echo
		'{{'			=> '<?php echo ',
		'}}'			=> ';?>',
		// Double & single reverse quotes - editors will highlight strings of code
		'``'			=> '"',			// Double - first
		'`'				=> "'",
		// push/pop stack: code ==> string
		'@push'			=> '<?php ob_start();?>',
		'@endpush'		=> '<?php partial_push(ob_get_clean());?>',
		'@pop'			=> '<?php partial_pop();?>',
		// @partial($key, $data) - компилировать фрагмент кода библиотеки и вернуть его БЕЗ echo
		'@partial'		=> '<?php partial_grab();?>',
		// @obtain(section) - возвращает код секции БЕЗ echo, тогда как @yield делает echo
		'@obtain'		=> '<?php partial_yield;?>',
		// Закрывающие кнструкции
		'@endforany'	=> '<?php endforeach; endif;?>',
	];

	// Замены preg_replace() конструкций Blade на аналоги Php
	public static $preg_replace = [
		// Вьюхи: include() - с локальными переменными шаблона, render() - без, только заданные
/*		'#@snippet\s*\((.*)\)#'			=> 'function() { return snippet($1); }',*/
		'#@render\s*\((.*),(.*)\)#'		=> '<?php echo view($1,$2)->render();?>',
		'#@render\s*\((.*)\)#'			=> '<?php echo view($1)->render();?>',
		'#@include\s*\((.*),(.*)\)#'	=> '<?php echo view($1,$2+get_defined_vars())->render();?>',
		'#@include\s*\((.*)\)#'			=> '<?php echo view($1,get_defined_vars())->render();?>',
		// Управляющие конструкции ::= @if (<expr>)\n !!! однострочный, завершается на последней в строке скобке ')'
		'#@(if|elseif|foreach|for|while)(\s*\(.*\))#'	=> '<?php $1$2:?>',
		// Управляющие конструкции ::= @if (<expr>)\n !!! однострочный, завершается на последней в строке скобке ')'
		'#@else(\s)#'									=> '<?php else:?>$1',
		'#@(endif|endforeach|endfor|endwhile)(\s)#'		=> '<?php $1;?>$2',
		//	@forany(<arr_expr> as <item_expr>)	>>>	if (isset(<arr_expr>)): foreach (<arr_expr> as <item_expr>):
		'#(\s*)@forany\s*\((.*)\s+as\s+(.*)\)#'			=> '$1<?php if (isset($2)): foreach ($2 as $3):?>',
	];

	// Замены preg_match() конструкций Blade на аналоги Php
	public static $preg_match = [
		// Сниппеты: шаблоны, текст которых вставляется напрямую в код
	//	'#@snippet\s*\((.*)\)#'	=> [__CLASS__, 'snippet'],
		'#@snippet\s*\((.*)\)#'	=> 'snippet',
	];

	// Алиасы используемых классов (вставить непосредственно в строки не получается:)
	// !!!!	Лучше использовать хелперы в глобе: view(), partial() !!!!
//	public static $class_aliases = [
//		'@View'		=> View::class,
//		'@Partial'	=> Partial::class,
//	];

	/*
		Компилятор
	*/
	public static function compile($code)
	{
		// удаляем Blade comments!!! Родной метод преобразует Blade comments into PHP comments.
		// Разрешить одностроковые комменты внутри многостроковых
	//	$value = preg_replace('/\{\{--(.+?)(--\}\})?\n/', '', $value);
	//	$value = preg_replace('/\{\{--((.|\s)*?)--\}\}/', '', $value);

		// Многострочный echo - Laravel\Blade допускает только однострочный
	//	$value = preg_replace('/\{\{((.|\s)*?)\}\}/', '<?php echo $1; ?'.'>', $value);

		//	@partial($key, $data) - исполнить фрагмент кода и вывести его вывод
	//	$pattern = '/@partial(\s*\((.|\s)*\))/';
	//	$pattern = static::ml_matcher('partial');
	//	$value = preg_replace($pattern, '\\Vsd\\Presenter\\Partial::play$1', $value);
	//	$value = preg_replace($pattern, '\\Vsd\\Presenter\\Partial::play$1', $value);

		//	@obtain(section) - возвращает код секции, тогда как $yield выводит его
		//	Нужен для передачи кода как аргумента фрагментов
	//	$pattern = '/@obtain(\s*\(([^\)]*)\))/';
	//	$value = preg_replace($pattern, '\\Laravel\\Section::$sections[$1]', $value);

		//	@forany(<arr_expr> as <item_expr>)	>>>	if (isset(<arr_expr>)): foreach (<arr_expr> as <item_expr>):
	//	$pattern = '#(\s*)@forany\s*\((.*)\s+as\s+(.*)\)#';
	//	$value = preg_replace($pattern, '$1<?php if (isset($2)): foreach ($2 as $3):?'.'>', $value);

		$code = preg_replace(array_keys(static::$preg_before), array_values(static::$preg_before), $code);
		$code = str_replace(array_keys(static::$str_replace), array_values(static::$str_replace), $code);
		$code = preg_replace(array_keys(static::$preg_replace), array_values(static::$preg_replace), $code);
	//	$code = str_replace(array_keys(static::$class_aliases), array_values(static::$class_aliases), $code);

		// Snippets - import template 
		foreach (static::$preg_match as $pattern => $callback)
		{
			// $callback could be either callable [class, method] | 'class::method', or this class method
			if (is_string($callback) and strpos($callback, '::') === FALSE) {
				$callback = [static::class, $callback];
			}
			$code = preg_replace_callback($pattern, $callback, $code);
		}

		return $code;
	}

	/**
	 * Helper: Return the contents of a given snippet (blade or php template file)
	 * 
	 * @param  array	$match	[pattern, source]
	 * @return string
	 */
	protected static function snippet($match)
	{
		// Remove trailing parentthenses: preg_xxx() makes literal matching
		// so any string in parentheses will still contain them in the match
		$template = trim($match[1], '"\'');

		return snippet($template);
	}

	/*
		Get the regular expression for a generic Blade function with multi line syntax
	protected static function ml_matcher($function)
	{
		return '/@'.$function.'(\s*\([^\)]*\))/';
	}
	*/
}
?>