<?php
namespace Air\Database;

use Air\Database\Query\IBuilder;

interface IModel
{
    public static function query() : IQuery;
    public static function call(IConnection $connection = null, IBuilder $builder = null) : ICall;
    public static function connection(IConnection $connection) : IModel;

    public static function getConnection() : IConnection;
	public static function getDriver() : string;
}