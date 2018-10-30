<?php
namespace Air\Database\Model;

use Air\Database\Connection;
use Air\Database\Query;

class Model implements \Air\Database\Model
{
    private static $instance = null;

    protected $driver;
    protected $connection;

    protected $database;
    protected $table;
    protected $key;

    public static function query() : Query
    {
        $className = dirname(__NAMESPACE__).'\\Query\\'.ucfirst(static::newSelf()->getDriver());

        return new $className(static::newSelf());
    }

    public function getDriver()
    {
        return static::newSelf()->driver;
    }

    public function getDatabase()
    {
        return static::newSelf()->database;
    }

    public function getTable()
    {
        return static::newSelf()->table;
    }

    public function getKey()
    {
        return static::newSelf()->key;
    }

    public function getReadConnection() : Connection
    {

    }

    public function getWriteConnection() : Connection
    {
        return new Connection\Mongo();
    }

    public static function newSelf()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }

        return self::$instance;
    }
}