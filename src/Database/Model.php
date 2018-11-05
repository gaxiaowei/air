<?php
namespace Air\Database;

interface Model
{
    public static function query() : Query;
    public static function process() : Process;

    public function getReadConnection() : Connection;
    public function getWriteConnection() : Connection;

	public function getDriver();
	public function getDatabase();
	public function getTable();
	public function getKey();
}