<?php
namespace Air\Database\Query;

use Air\Database\Model;

trait QueryTrait
{
    /**@var $model Model*/
    private $model;

    private $column;
    private $order;
    private $offset = 0;
    private $limit = 0;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function order(string $field, string $direction = 'ASC')
    {
        $this->order[$field] = $direction;

        return $this;
    }

    public function orderAsc(string $field)
    {
        return $this->order($field, 'ASC');
    }

    public function orderDesc(string $field)
    {
        return $this->order($field, 'DESC');
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function take(int $limit)
    {
        return $this->limit($limit);
    }

    public function offset(int $offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function skip(int $offset)
    {
        return $this->offset($offset);
    }
}