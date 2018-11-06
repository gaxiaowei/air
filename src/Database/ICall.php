<?php
namespace Air\Database;

use Air\Database\Query\IBuilder;

interface ICall
{
    public function fetch();
    public function fetchAll();
    public function execute();

    public function setConnection(IConnection $connection) : ICall;
    public function getConnection() : IConnection;

    public function setBuilder(IBuilder $builder) : ICall;
    public function getBuilder() : IBuilder;
}