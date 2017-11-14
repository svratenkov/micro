<?php
/*
	Application - app level container

	Responsibilities:
	- Routes HTTP request
	- Creates IoC Container common for all MVC components
	- Handles PageController for corresponding view & model
	- Performs HTTP respones

	ASP.NET Front Controller (https://msdn.microsoft.com/ru-ru/library/ms978723.aspx)
		The handler has two responsibilities: 
		- Retrieve parameters.
			The handler receives the HTTP Post or Get request from the Web server
			and retrieves relevant parameters from the request. 
		- Select commands.
			The handler uses the parameters from the request first to choose the correct command
			and then to transfer control to the command for processing.
*/
namespace Micro\Core;
use Transformic\DotTransformic;

class Application extends DotTransformic
{
}
