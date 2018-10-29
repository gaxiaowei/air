<?php
namespace Air\Orm\Connection;

use Air\Orm\Connection;

class Pdo extends \PDO implements Connection
{
	public function __construct(string $dsn='mysql://localhost:3306', string $user='root', string $password='', array $options=null)
	{
		if(strpos(strtolower($dsn), 'charset=') !== false) {
			preg_match('/charset=([a-z0-9-]+)/i', $dsn, $match);
			$charset = isset($match[1]) ? $match[1] : 'utf8';
		} else {
            $dsn .= (substr($dsn, -1)===';' ? '' : ';')."charset={$charset}";
			$charset = isset($options['charset']) ? $options['charset'] : 'utf8';
		}

		try {
			parent::__construct($dsn, $user, $password, array(\PDO::ATTR_PERSISTENT => false));
		} catch (\PDOException $e) {
			throw new \InvalidArgumentException('Connection failed: '.$e->getMessage(), $e->getCode());
		}

		$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

		$timezone = isset($options['timezone']) ? $options['timezone'] : '+00:00';
		$this->exec("SET time_zone='{$timezone}'");
		$this->exec("SET NAMES '{$charset}'");
	}

	public function begin() : bool
	{
		if ($this->inTransaction()===false) {
			return parent::beginTransaction();
		}

		return false;
	}

	public function commit() : bool
	{
		if($this->inTransaction()===true) {
			return parent::commit();
		}

		return false;
	}

	public function rollback() : bool
	{
		if ($this->inTransaction()===true) {
			return parent::rollback();
		}

		return false;
	}
}
