<?php
namespace Air\Database;

interface IModel
{
    public static function query() : IQuery;
    public static function call() : ICall;
    public static function connection(IConnection $connection) : IModel;

    public static function getConnection() : IConnection;
	public static function getDriver() : string;
}