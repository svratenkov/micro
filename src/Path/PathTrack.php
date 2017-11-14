<?php
/*
	PathTrack - трек - расширенный алиас с точечной нотацией
	Трек (расширенный алиас) - сегменты диров с разделителями, начинающиеся с алиаса с точкой
		<track> ::= <alias>{.<segment>{[./\]<segment>}}

!!!	Некорректный путь алиаса вызывает трудно отлавливаемые наведеннные ошибки.
!!!	Поэтому методы этого класса генерят исключения для некорректного пути
!!!	Однако они имеют аргумент, позволяющий отключать исключения

	Методы треков:
		PathTrack::get($track, $raise)			--> вернуть абс путь заданного трека
		PathTrack::file($track, $file, $raise)	--> вернуть абс путь файла (с проверкой) в пути заданного трека
		PathTrack::dir($track, $dir, $raise)	--> вернуть абс путь директория (с проверкой) в пути заданного трека
*/
namespace Micro\Path;

class PathTrack extends PathAlias
{
	/*
		Вернуть абс. путь трека
	*/
	public static function get($track, $raise = TRUE)
	{
		// Разбиваем трек на алиас и дир
		list($alias, $dir) = static::parse_track($track, NULL, $raise);
//dd(__METHOD__, $track, $subdir, $alias, $dir);

		// Пользуем родительский метод алиаса
		return $alias ? parent::get($alias, $dir, $raise) : FALSE;
	}

	/*
		Вернуть абсолютный путь к файлу в пути с алиасами и точками
		Добавляет заданное (дефолтное) расширение
	*/
	public static function file($track, $file, $raise = TRUE)
	{
		// Разбиваем трек на алиас и дир
		list($alias, $dir) = static::parse_track($track, $file, $raise);

		// Пользуем родительский метод алиаса
		return $alias ? parent::file($alias, $dir, $raise) : NULL;
	}

	/*
		Вернуть абсолютный путь к файлу в пути с алиасами и точками
		Добавляет заданное (дефолтное) расширение
	*/
	public static function dir($track, $dir, $raise = TRUE)
	{
		// Разбиваем трек на алиас и дир
		list($alias, $subdir) = static::parse_track($track, $dir, $raise);

		// Пользуем родительский метод алиаса
		return $alias ? parent::dir($alias, $subdir, $raise) : FALSE;
	}

	/*
		Лошадка: разбить трек на алиас и дир
		М.б. задан поддир - для удобства, просто чтоб не писать:
			$track.{DIRECTORY_SEPARATOR | [/.\]}.$subdir,
		Возвращает массив пути трека: алиас + дир от алиаса
			array(
				'alias',	// имя алиаса или NULL, если первый сегмент трека - не алиас
				'dir'		// дир в пути алиаса или NULL, если исходный трек - чисто алиас
			)
		Заменяет все разделители [./\] в dir на DIRECTORY_SEPARATOR
	*/
	public static function parse_track($track, $subdir = NULL, $raise = TRUE)
	{
		// Разбиваем трек на алиас (сегмент до первой точки) и дир - остаток трека
		if (($pos = strpos($track, '.')) === FALSE) {
			// В треке нет ни одной точки, весь трек - алиас
			$alias = $track;
			$dir = '';
		}
		else {
			// Точка есть - она разделяет алиас (первый сегмент) и дир
			$alias = substr($track, 0, $pos);
			$dir = substr($track, $pos + 1);
		}

		// Обработка отсутствующего алиаса
		if (! isset(static::$aliases[$alias])) {
			if ($raise) {
				throw new \Exception("Passed track `{$track}` begins with undefined alias `{$alias}`.");
			}
			return array(NULL, $track);
		}

		// Алиас есть - добавляем под-дир - все точки в нем будут заменены тоже
		if ($subdir) {
			if ($dir) {
				$dir .= DIRECTORY_SEPARATOR.$subdir;
			}
			else {
				$dir = $subdir;
			}
		}
		$dir = str_replace('.', DIRECTORY_SEPARATOR, $dir);

		return array($alias, $dir);
	}
}
