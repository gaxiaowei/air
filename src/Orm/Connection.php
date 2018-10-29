<?php
namespace Air\Orm;

interface Connection
{
	public function begin():bool;
	public function commit():bool;
	public function rollback():bool;
}
