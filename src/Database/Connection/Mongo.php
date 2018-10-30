<?php
namespace Air\Database\Connection;

use Air\Database\Connection;

class Mongo implements Connection
{
    private $connection = null;

	public function __construct(string $dsn='mongodb://192.168.30.100:27018', array $options = [])
	{
        $options = $options === null ? ['w' => 1] : $options;

        if (!isset($options['maxPoolSize'])) {
            $options['maxPoolSize'] = 10;
        }

        try {
            $manager = new \MongoDB\Driver\Manager($dsn, $options);
            $command = new \MongoDB\Driver\Command(['ping' => 1]);

            $manager->executeCommand('db', $command);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Connection failed: '.$e->getMessage(), $e->getCode());
        }

        $this->connection = $manager;
	}

	public function begin() : bool
	{
		return false;
	}

	public function commit() : bool
	{
		return false;
	}

	public function rollback() : bool
	{
		return false;
	}

	public function __call($name, $arguments)
    {
        return call_user_func_array([$this->connection, $name], $arguments);
    }

    public function __destruct()
    {
        unset($this->connection);
    }
}