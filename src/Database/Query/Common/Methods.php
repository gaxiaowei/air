<?php
namespace Air\Database\Query\Common;

use Air\Database\IQuery;

trait Methods
{
    private $database = '';
    private $table = '';
    private $key = '';

    private $step = 0;
    private $type = null;

    private $select = null;
    private $where = '';
    private $groupBy = '';
    private $having = '';
    private $order = [];
    private $offset = null;
    private $limit = null;

    private $whereParameters = [];
    private $havingParameters = [];

    private $data = [];

    public function __construct(string $database = '', string $table = '')
    {
        $this->database = $database;
        $this->table = $table;
    }

    public function order(string $field, string $direction = 'ASC') : IQuery
    {
        if (false !== strrpos(get_class($this), 'Mongo')) {
            $direction = strtolower($direction) === 'asc' ? 1 : -1;
        }

        $this->order[$field] = $direction;

        return $this;
    }

    public function orderAsc(string $field) : IQuery
    {
        return $this->order($field, 'ASC');
    }

    public function orderDesc(string $field) : IQuery
    {
        return $this->order($field, 'DESC');
    }

    public function limit(int $limit) : IQuery
    {
        $this->limit = $limit;

        return $this;
    }

    public function take(int $limit) : IQuery
    {
        return $this->limit($limit);
    }

    public function offset(int $offset) : IQuery
    {
        $this->offset = $offset;

        return $this;
    }

    public function skip(int $offset) : IQuery
    {
        return $this->offset($offset);
    }

    public function setDatabase(string $database) : IQuery
    {
        $this->database = $database;

        return $this;
    }

    public function setTable(string $table) : IQuery
    {
        $this->table = $table;

        return $this;
    }

    public function setKey(string $key) : IQuery
    {
        $this->key = $key;

        return $this;
    }

    private function reset()
    {
        $this->database = '';
        $this->table = '';

        $this->step = 0;
        $this->type = null;

        $this->select = null;
        $this->where = '';
        $this->groupBy = '';
        $this->having = '';
        $this->order = [];
        $this->offset = null;
        $this->limit = null;

        $this->whereParameters = [];
        $this->havingParameters = [];

        $this->data = [];
    }
}