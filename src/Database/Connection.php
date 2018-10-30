<?php
namespace Air\Database;

interface Connection
{
	public function begin() : bool;
	public function commit() : bool;
	public function rollback() : bool;

	public function __destruct();
	public function __call($name, $arguments);
}
