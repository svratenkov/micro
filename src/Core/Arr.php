<?php
/*
	Утилиты для работы с массивами
*/
namespace Micro\Core;

class Arr
{
	/*
		Вернуть значение первого элемента массива или FALSE для пустого массива
	*/
	public static function first($array)
	{
		return reset($array);
	}

	/*
		Вернуть значение первого ключа массива или NULL для пустого массива
	*/
	public static function first_key($array)
	{
		return reset($array) !== FALSE ? key($array) : NULL;
	}

	/*
		Вернуть значение последнего элемента массива или FALSE для пустого массива
	*/
	public static function last($array)
	{
		return end($array);
	}

	/*
		Вернуть значение последнего ключа массива или NULL для пустого массива
	*/
	public static function last_key($array)
	{
		return end($array) !== FALSE ? key($array) : NULL;
	}

	/*
		Сортировка массива без изменения ключей
		Функции сортировки PHP берут ссылку и изменяют сам массив
		Эта сортирует и возвращает копию
	*/
	public static function asort($array, $sort_flags = SORT_REGULAR)
	{
		asort($array, $sort_flags);
		return $array;
	}

	/*
		Разбить массив на $n под-массивов одинаковой размерности
		Возвращает 2-мерный индексный массив под-массивов с сохраненными ключами
		Используется для разбивки страниц
	*/
	public static function divide($array, $n = 2)
	{
		$len = count($array);				// размерность массива
		$m = (int) ceil($len / $n);			// размерность под-массива
		$tmp = array();

		for ($i = 0; $i < $len; $i += $m) {
			$tmp[] = array_slice($array, $i, $m);
		}

		return $tmp;
	}

	/*
		Ищет в заданной строке subject совпадения с одним из шаблонов массива patterns
		Массив шаблонов: array(pattern => value)
		Возвращает значение первого соответствия в массиве паттернов, или дефолт $default

		Пример: определить среду приложения по хосту и массиву шаблонов:
			$env = Arr::preg_match(
				$_SERVER['HTTP_HOST'],
				array(
					'(www.projectbureau.ru|www.projectbureau.loc)'	=> 'production',
					'(tst.projectbureau.ru|tst.projectbureau.loc)'	=> 'development',
					'test',					// дефолт - спец значение для дефолта в массиве
				),
				'test'						// дефолт в аргументе
			);
	*/
	public static function preg_match($subject, $patterns, $default = NULL)
	{
		// Ищем первое соответствие хоста в массиве паттернов
		foreach ($patterns as $pattern => $value) {
		//	if (is_int($pattern) or preg_match('#'.$pattern.'#', $subject)) {	// поддержка дефолта в массиве
			if (preg_match('#'.$pattern.'#', $subject)) {						// БЕЗ поддержки дефолта в массиве
				return $value;
			}
		}

		// Если добрались сюда - значит, совпадений нет, дефолта в массиве нет, возвращаем основной дефолт
		return $default;
	}

	/*
		Подстановка в строке или массиве всем плэйсхолдерам ':key' их заданные значения
		Модификация Arr::replace() с передачей подсановок одним массивом
			$replacements = array(':search_key' => 'replace_value',...)
		Используется в Config, Lang
	*/
	public static function substitute($subject, $replacements, $mode = NULL)
	{
		// Если массив
		if (is_array($subject)) {
			// Готовим подстановки для основного метода
			foreach ($replacements as $key => $value) {
				$search[]	= ':'.$key;
				$replace[]	= $value;
			}

			// Основной метод
			return static::replace($search, $replace, $subject);
		}

		// Здесь - строка - меняем каждую замену
		foreach ($replacements as $key => $value) {
			$subject = str_replace(':'.$key, $value, $subject);
		}
		return $subject;
	}

	/*
		Аналог str_replace() для многомерного массива
	*/
	public static function replace($search, $replace, array $subject, $mode = 'handmade')
	{
		if ($mode === 'json') {
			// Json - элегантный И БЫСТРЫЙ способ:
			return json_decode(static::substitute(json_encode($subject), array_combine($search, $replace)), TRUE); 
		}

		if ($mode === 'handmade') {
			// Рекурсивная обработка - просто, надежно, код под контролем
			return static::hm_replace($search, $replace, $subject);
		}

		// Дефолтный метод - просто и без самопала
		array_walk_recursive(
			$subject,
			function(&$value, $key, $args) {
				$value = str_replace($args[0], $args[1], $value);
			},
			array($search, $replace)
		);
		return $subject;
	}

	/*
		{Home/Hand}Made - Самопал - лошадка рекурсивной замены
	*/
	public static function hm_replace($search, $replace, $subject)
	{
		$new = array();
		foreach ($subject as $key => $value) {
			if (is_array($value)) {
				$new[$key] = static::arr_replace($search, $replace, $value);
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

	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	public static function only($array, $keys)
	{
		return array_intersect_key( $array, array_flip((array) $keys) );
	}

	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	public static function except($array, $keys)
	{
		return array_diff_key( $array, array_flip((array) $keys) );
	}

	/**
	 * Check given item existence in the given array using "dot" notation.
	 *
	 *	Вместо конструкций:
	 *		if (isset($config['sectors']['content']))
	 *			$val = $config['sectors']['content'];
	 *	Будет:
	 *		$val = Arr::get('sectors.content');
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return bool
	 */
	public static function has(array $array, $key)
	{
		// To retrieve the array item using dot syntax, we'll iterate through
		// each segment in the key and look for that value. If it exists,
		// we will set the depth of the array and look for the next segment.
		// Otherwise we will return it, 
		foreach (explode('.', $key) as $segment)
		{
			if (! isset($array[$segment]) or ! array_key_exists($segment, $array)) {
				return FALSE;
			}

			$array = $array[$segment];
		}

		return TRUE;
	}

	/**
	 * Get an item from an array using "dot" notation.
	 * If an item does not exists returns given default.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get(array $array, $key = NULL, $default = NULL)
	{
		if (is_null($key)) {
			// Вернуть весь массив
			return $array;
		}

		foreach (explode('.', $key) as $segment) {
			if (! is_array($array) or ! isset($array[$segment]) or ! array_key_exists($segment, $array)) {
				return $default;
			}
			$array = $array[$segment];
		}

		return $array;
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public static function set(array & $array, $key, $value)
	{
		if (is_null($key)) return $array = $value;

		$keys = explode('.', $key);

		// This loop allows us to dig down into the array to a dynamic depth by
		// setting the array value for each level that we dig into. Once there
		// is one key left, we can fall out of the loop and set the value as
		// we should be at the proper depth.
		while (count($keys) > 1) {
			$key = array_shift($keys);

			// If the key doesn't exist at this depth, we will just create an
			// empty array to hold the next value, allowing us to create the
			// arrays to hold the final value.
			if (! isset($array[$key]) or ! is_array($array[$key])) {
				$array[$key] = array();
			}

			$array =& $array[$key];
		}

		$array[array_shift($keys)] = $value;
	}

	/**
	 * Remove an array item from a given array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return void
	 */
	public static function forget(array & $array, $key)
	{
		$keys = explode('.', $key);

		// This loop functions very similarly to the loop in the "set" method.
		// We will iterate over the keys, setting the array value to the new
		// depth at each iteration. Once there is only one key left, we will
		// be at the proper depth in the array.
		while (count($keys) > 1) {
			$key = array_shift($keys);

			// Since this method is supposed to remove a value from the array,
			// if a value higher up in the chain doesn't exist, there is no
			// need to keep digging into the array, since it is impossible
			// for the final value to even exist.
			if (! isset($array[$key]) or ! is_array($array[$key])) {
				return;
			}

			$array =& $array[$key];
		}

		unset($array[array_shift($keys)]);
	}

	/*
		Для заданного массива сформировать его PHP-представление
		Возвращает строку
		Выводить на экран надо в тэге <pre></pre>
			$plain_leaves	- TRUE: массив-лист дерева - горизонтально
			$tabs			- отступ табов очередного уровня
	*/
	public static function as_php(array $array, $plain_leaves = FALSE, $tabs = '')
	{
		// Размер таба - полей (пробелов) в табе
		$tabsize = 4;

		// Формируем массив php-представлений элементов: key => val
		$elts = array();
		$inc = 0;						// авто-инкремент индекса
		$has_subarrays = FALSE;			// Есть ли вложенные под-массивы?
		$keys = $vals = $lens = array();
		$maxlen = 0;

		foreach ($array as $key => $val)
		{
			// Сначала ключ элемента
			if (is_int($key)) {
				if ($key == $inc) {
					// Индекс совпадает с автоинкрементом - НЕ выводим его и обновляем автоинкремент
					$inc++;
					$key = '';
				}
				else {
					// Индекс не совпадает с автоинкрементом - выводим его и обновляем автоинкремент
					$inc = $key + 1;
					$key = (string) $key;
				}
			}
			else {
				// Ассоциативный ключ - обрамляем одинарными кавычками
				$key = "'".$key."'";
			}

			// Длина ключа - для вертикального выравнивания
			if (($len = strlen($key)) > $maxlen) {
				$maxlen = $len;
			}
			$keys[] = $key;
			$lens[] = $len;

			// Теперь значение элемента
			if (is_array($val)) {
				$has_subarrays = TRUE;
				$val = static::as_php($val, $plain_leaves, $tabs."\t");
			}
			else if (is_int($val)) {
				// Ничего не меняем
			}
			else {
				// Не массив и не целое - автопреобразование в строку в кавычках
				$val = "'".$val."'";
			}

			// Сохраняем php-представление элемента в масиве
			$elts[] = $key.$val;
			$vals[] = $val;
		}

		// Кол-во табов в самом длинном ключе + собственно таб
		$maxsize = 1 + (int) ($maxlen / $tabsize);

		// Формируем php-представления элементов: key => val с выравниванием позиции значений
		//$elts = php_array_elements($array);

		if (! $has_subarrays and $plain_leaves) {
			// вложенных под-массивов НЕТ и НЕ задано вертикальное выравнивание листьев
			// элементы этого массива выравниваются горизонтально без отступов
			foreach ($keys as $i => $key) {
				$elts[] = $key.' => '.$vals[$i];
			}
			$php = implode(', ', $elts);
		}
		else {
			// вложенные под-массивы ЕСТЬ или задано вертикальное выравнивание листьев
			// элементы этого массива выравниваются вертикально с увеличенным отступом

			// Формируем php-представления элементов: key => val с верт.выравниванием позиции оператора =>
			$elts = array();
			foreach ($keys as $i => $key) {
				$val = $vals[$i];
				if ($key) {
					if ($has_subarrays) {
						$stabs = "\t";
					}
					else {
						// Размер этого ключа в табах
						$size = (int) ($lens[$i] / $tabsize);
						$stabs = str_repeat("\t", $maxsize - $size);
					}
					$elts[] = $key.$stabs.'=> '.$val;
				}
				else {
					$elts[] = $val;
				}
			}

			$php = '';
			foreach ($elts as $val) {
				$php .= PHP_EOL.$tabs."\t".$val.',';
			}
			$php .= PHP_EOL.$tabs;
		}

		return 'array('.$php.')';
	}
}
?>