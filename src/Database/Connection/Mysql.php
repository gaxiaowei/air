<?php
namespace Air\Database\Connection;

use Air\Database\Connection;

class Mysql implements Connection
{
    private $connection = null;

	public function __construct()
	{

	}

	public function begin() : bool
	{

	}

	public function commit() : bool
	{

	}

	public function rollback() : bool
	{

	}

	public function __destruct()
    {
        unset($this->connection);
    }
}
