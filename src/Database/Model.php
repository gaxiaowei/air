<?php
namespace Air\Database;

interface Model
{
    public static function query();

    public function getReadConnection() : Connection;
    public function getWriteConnection() : Connection;

	public function getDriver();
	public function getDatabase();
	public function getTable();
	public function getKey();
}