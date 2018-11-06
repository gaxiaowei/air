<?php
namespace Air\Database\Connection;

use Air\Database\IConnection;

class Pdo implements IConnection
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

	public function __call($name, $arguments)
    {

    }

    public function __destruct()
    {
        unset($this->connection);
    }
}
