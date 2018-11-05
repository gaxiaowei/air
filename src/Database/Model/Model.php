<?php
namespace Air\Database\Model;

use Air\Database\Connection;
use Air\Database\Process;
use Air\Database\Query;

class Model implements \Air\Database\Model
{
    private static $instance = null;
    protected static $query = [];
    protected static $process = [];

    protected $driver;
    protected $connection;

    protected $database;
    protected $table;
    protected $key;

    public static function query() : Query
    {
        $className = dirname(__NAMESPACE__) . '\\Query\\' . ucfirst(static::newSelf()->getDriver());
        if (!isset(static::$query[static::class])) {
            static::$query[static::class] = new $className(static::newSelf());
        }

        return static::$query[static::class];
    }

    public static function process() : Process
    {
        $query = static::query()->queryBuild();
        unset(static::$query[static::class]);

        $className =  dirname(__NAMESPACE__).'\\Process\\'.ucfirst(static::newSelf()->getDriver());
        if (!isset(static::$process[static::class])) {
            static::$process[static::class] = new $className(static::newSelf(), $query);
        }

        return static::$process[static::class];
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
        return new Connection\Mongo();
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