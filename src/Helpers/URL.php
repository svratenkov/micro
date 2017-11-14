<?php
/*
	Утилиты для работы с URL-ами
	Формирование путей к разделам сайта
*/
// Global namespace

class URL
{
	// Единый тип урлов на сайте - абсолютные или относительные
	public static $url_abs = TRUE;

	/*
		Вернуть ссылку к странице сайта (домашней)
	*/
	public static function to($uri = '')
	{
		return Request::base_url(static::$url_abs).$uri;
	}

	/*
		Вернуть полную ссылку к ресурсу сайта
	*/
	public static function to_asset($uri)
	{
		return static::to(Config::get('app.assets').'/'.$uri);
	}

	/*
		Вернуть домен сайта - часто ресурсы называются именем домена
	*/
	public static function base()
	{
		return Request::$base;
	}
}
?>