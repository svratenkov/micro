<?php
/*
	PathFile - хранилище алиасов путей к директориям сервера

	Все возвращаемые пути:
	-	нормализованы, символы [/\] заменены на DIRECTORY_SEPARATOR
	-	завершеются разделителем директория DIRECTORY_SEPARATOR
	-	существют на сервере

!!!	Некорректный путь алиаса вызывает трудно отлавливаемые наведеннные ошибки
!!!	Поэтому методы этого класса генерят исключения для некорректного пути

	Методы алиасов:
-	PathAlias::aliases()					--> вернуть массив алиасов
-	PathAlias::has($alias)					--> определен ли заданный алиас?
-	PathAlias::get($alias, $file)			--> вернуть путь заданного файла в пути заданного алиаса (без проверки)
-	PathAlias::file($alias, $file, $raise)	--> вернуть абс путь файла в пути заданного алиаса (с проверкой)
-	PathAlias::dir($alias, $dir, $raise)	--> вернуть абс путь директория в пути заданного алиаса (с проверкой)
-	PathAlias::set($alias, $path, $raise)	--> определить заданный алиас и его путь (без проверки)
-	PathAlias::map($map, $raise)			--> установить карту (массив относительных путей) алиасов (без проверки)
*/
namespace Micro\Path;

class PathFile extends PathAlias
{
	/**
	 * Вернуть абс.путь заданного файла в пути заданного алиаса
	 * !!! Генерит исключение при некорректностях !!!
	 * 
	 * @param  string $alias
	 * @param  string $file
	 * @return string
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

	/**
	 * Вернуть абс.путь заданного директория в пути заданного алиаса
	 * Возвращаемый путь всегда завершается DIRECTORY_SEPARATOR
	 * !!! Генерит исключение при некорректностях !!!
	 * 
	 * @param  string $alias
	 * @param  string $dir
	 * @return string
	 */
	public function dir($alias, $dir = NULL)
	{
		return $this->file($alias, $dir).$this->separator;
	}
}
