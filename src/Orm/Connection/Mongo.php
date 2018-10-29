<?php
namespace Air\Orm\Connection;

use Air\Orm\Connection;

class Mongo implements Connection
{
	protected $_manager  = null;
	protected $_database = null;

	public function __construct(string $dsn='mongodb://localhost:27017', array $options = null)
	{
		$options = $options===null ? array('w'=>1) : $options;

		if(preg_match('/\/([a-z0-9_]+)(?:\?|$)/i', $dsn, $matches)) {
			$this->_database = $matches[1];
		} else {
			$this->_database = 'development';
		}

		if(!isset($options['maxPoolSize'])) {
			$options['maxPoolSize'] = 10;
		}

		try {
			$manager = new \MongoDB\Driver\Manager($dsn, $options);
			$command = new \MongoDB\Driver\Command(['ping' => 1]);
			$manager->executeCommand('db', $command);

		} catch (\exception $e) {
			throw new \InvalidArgumentException('Connection failed: '.$e->getMessage(), $e->getCode());
		}

		$this->_manager = $manager;
	}

	public function getManager()
	{
		return $this->_manager;
	}

	public function getDatabase()
	{
		return $this->_database;
	}

	public function begin():bool
	{
		return false;
	}

	public function commit():bool
	{
		return false;
	}

	public function rollback():bool
	{
		return false;
	}
}
