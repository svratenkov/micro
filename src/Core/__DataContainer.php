<?php
/*
	DataContainer - data holder
	From: https://gist.github.com/dhrrgn/d178500e5e566da00cff
*/
namespace Micro\Core;

use ArrayAccess;
use Countable;
use IteratorAggregate;

class DataContainer implements ArrayAccess, Countable, IteratorAggregate
{
	use DataContainerTrait;
}
