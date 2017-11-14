<?php
/*
	Дамп переменных - хелперы в Глобе
	Использует Kohana Debug Чтоб работал красивый дамп, надо предварительно инклудить ErrorDebug.php
*/
use Micro\Error\Debug;

// Dump
function dd()
{
	foreach (func_get_args() as $var) {
		dv($var);
	}
}

// Dump & Exit
function de(...$args)
{
//	if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
		dd(...$args);
//	else {
//		foreach (func_get_args() as $var) {
//			dd($var);
//		}
//	}
	exit;
}

// Дамп одной переменной с вариациями, если есть ErrorDebug, иначе - причесанный var_dump()
function dv($var)
{
	echo class_exists(Debug::class, FALSE) ? Debug::dump_styled($var) : dump_var($var);
}

// Улучшенный var_dump()
function dump_var($var)
{
	static $meta_charset;

	// Русская кодировка - Один лишь раз сады цветут...
	if (is_null($meta_charset)) {
		echo '<meta charset="UTF-8">';
		$meta_charset = FALSE;
	}

	ob_start();
	var_dump($var);
	$d = ob_get_clean();
	$d = str_replace(array("\r\n", "\r", "\n"), '<br>', $d);
	$d = str_replace('=><br>', ' => ', $d);
	$d = '<pre>'.$d.'</pre>';

	return $d;
}
