<?php
namespace Air\Database\Query;

interface IBuilder
{
    public function getDatabase();
    public function getTable();
    public function getKey();
    public function getField();
    public function getWhere();
    public function getGroup();
    public function getHaving();
    public function getLimit();
    public function getOffset();
    public function getOrder();
    public function getData();
    public function getAction();

    public function setAttribute($name, $value);
    public function getAttribute($name);
}