<?php
namespace Air\Database;

use Countable;
use Iterator;
use ArrayAccess;

interface Record extends Countable, Iterator, ArrayAccess
{
	public function __get(string $column);
	public function __set(string $column, $value);
	public function save():string;
	public function delete():bool;
	public function execute():string;
	public function toArray():array;
}