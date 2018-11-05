<?php
namespace Air\Database;

interface QueryBuild
{
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