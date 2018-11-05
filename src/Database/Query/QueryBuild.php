<?php
namespace Air\Database\Query;

class QueryBuild implements \Air\Database\QueryBuild
{
    public function getField()
    {
        return $this->field ?? null;
    }

    public function getWhere()
    {
        return $this->where ?? [];
    }

    public function getGroup()
    {
        return $this->group ?? [];
    }

    public function getHaving()
    {
        return $this->having ?? [];
    }

    public function getLimit()
    {
        return $this->limit ?? null;
    }

    public function getOffset()
    {
        return $this->offset ?? null;
    }

    public function getOrder()
    {
        return $this->order ?? [];
    }

    public function getAction()
    {
        return $this->action ?? null;
    }

    public function getData()
    {
        return$this->data ?? [];
    }

    public function getAttribute($name)
    {
        $this->$name;
    }

    public function setAttribute($name, $value)
    {
        $this->$name = $value;
    }
}