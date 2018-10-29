<?php
namespace Air\Database;

interface model
{
	public static function insert(array $data=null):record;
	public static function select(string $columns='*'):query;
	public static function update(array $data):query;
	public static function delete(array $data=null):query;

	public static function getDriver():string;
	public static function getMaster():\Air\Database\Connection;
	public static function getSlave():connection;
	public static function getDatabase():string;
	public static function getTable():string;
	public static function getKey():string;
}
