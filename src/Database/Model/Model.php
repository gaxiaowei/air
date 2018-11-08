<?php
namespace Air\Database\Model;

use Air\Database\ICall;
use Air\Database\IConnection;
use Air\Database\IModel;
use Air\Database\IQuery;
use Air\Database\Query\IBuilder;

class Model implements IModel
{
    protected static $instance = null;
    protected static $queryInstance = [];
    protected static $connectionInstance = [];
    protected static $callInstance = [];

    protected $driver;

    protected $database = '';
    protected $table = '';
    protected $key = '';

    public static function query() : IQuery
    {
        $className = static::getDriverClassName();
        if (!isset(static::$queryInstance[static::class])) {
            static::$queryInstance[static::class] = new $className;
        }

        static::$queryInstance[static::class]
            ->setDatabase(static::newSelf()->getDatabase())
            ->setTable(static::newSelf()->getTable())
            ->setKey(static::newSelf()->getKey());

        return static::$queryInstance[static::class];
    }

    public static function connection(IConnection $connection = null) : IModel
    {
        if (is_null($connection)) {
            $className = static::getConnectionClassName();
            if (!isset(static::$connectionInstance[static::class])) {
                static::$connectionInstance[static::class] = new $className;
            }
        } else {
            static::$connectionInstance[static::class] = $connection;
        }

        return static::newSelf();
    }

    public static function call(IConnection $connection = null, IBuilder $builder = null) : ICall
    {
        $className = static::getCallClassName();

        if (is_null($connection)) {
            $connection = static::getConnection();
        }

        if (is_null($builder)) {
            $builder = static::query()->build();
        }

        if (!isset(static::$callInstance[static::class])) {
            static::$callInstance[static::class] = new $className;
        }

        static::$callInstance[static::class]
            ->setConnection($connection)
            ->setBuilder($builder);

        return static::$callInstance[static::class];
    }

    public static function newSelf()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public static function getDriver() : string
    {
        return static::newSelf()->driver;
    }

    public static function getConnection() : IConnection
    {
        static::connection();

        return static::$connectionInstance[static::class];
    }

    private function getDatabase() : string
    {
        return static::newSelf()->database;
    }

    private function getTable() : string
    {
        return static::newSelf()->table;
    }

    private function getKey() : string
    {
        return static::newSelf()->key;
    }

    private static function getDatabaseNameSpace()
    {
        $namespace = __NAMESPACE__;
        $last = strrpos($namespace, '\\');

        return substr($namespace, 0, $last);
    }

    private static function getDriverClassName()
    {
        return static::getDatabaseNameSpace() . '\\Query\\' . ucfirst(static::newSelf()->getDriver());
    }

    private static function getCallClassName()
    {
        return static::getDatabaseNameSpace() . '\\Call\\' . ucfirst(static::newSelf()->getDriver());
    }

    private static function getConnectionClassName()
    {
        return static::getDatabaseNameSpace() . '\\Connection\\' . ucfirst(static::newSelf()->getDriver());
    }
}