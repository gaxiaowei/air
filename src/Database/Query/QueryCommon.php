<?php
namespace Air\Database\Query;

use Air\Database\Model;
use Air\Database\Query;

abstract class QueryCommon
{
    /**@var $model Model*/
    protected $model;

    protected $step = 0;
    protected $type = null;

    protected $select = null;
    protected $where = '';
    protected $groupBy = '';
    protected $having = '';
    protected $order = [];
    protected $offset = null;
    protected $limit = null;

    protected $whereParameters = [];
    protected $havingParameters = [];

    protected $data = [];

    const INSERT = 'INSERT';
    const SELECT = 'SELECT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';

    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    public function order(string $field, string $direction = 'ASC') : Query
    {
        if (false !== strrpos(get_class($this), 'Mongo')) {
            $direction = strtolower($direction)==='asc' ? 1 : -1;
        }

        $this->order[$field] = $direction;

        return $this;
    }

    public function orderAsc(string $field) : Query
    {
        return $this->order($field, 'ASC');
    }

    public function orderDesc(string $field) : Query
    {
        return $this->order($field, 'DESC');
    }

    public function limit(int $limit) : Query
    {
        $this->limit = $limit;

        return $this;
    }

    public function take(int $limit) : Query
    {
        return $this->limit($limit);
    }

    public function offset(int $offset) : Query
    {
        $this->offset = $offset;

        return $this;
    }

    public function skip(int $offset) : Query
    {
        return $this->offset($offset);
    }

    public function getModel() : Model
    {
        return $this->model;
    }

    public function setModel(Model $model)
    {
        return $this->model = $model;
    }
}