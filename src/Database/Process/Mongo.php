<?php
namespace Air\Database\Process;

use Air\Database\Model;
use Air\Database\Process;
use Air\Database\QueryBuild;
use MongoDB\BSON\Binary;
use MongoDB\Driver\BulkWrite;

class Mongo implements Process
{
    /**@var $model Model*/
    private $model;

    /**@var $query QueryBuild**/
    private $queryBuild;

    public function __construct(Model $model = null, QueryBuild $queryBuild = null)
    {
        $this->model = $model;
        $this->queryBuild = $queryBuild;
    }

    public function fetch()
    {
        $this->queryBuild->setAttribute('limit', 0);
        $this->queryBuild->setAttribute('offset', 1);

        return $this->fetchAll()[0] ?? [];
    }

    public function fetchAll()
    {
        $result = [];

        $query  = new \MongoDB\Driver\Query(
            $this->getWhere(),
            $this->getOptions()
        );

        $cursor = $this->getModel()
            ->getReadConnection()
            ->executeQuery(
                $this->getModel()->getDatabase().'.'.$this->getModel()->getTable(),
                $query
            )->toArray();

        foreach ($cursor as $row) {
            $row = (array)$row;

            $result[] = $this->stdToArray($row);
        }

        return $result;
    }

    public function execute()
    {
        $bulkWrite = new BulkWrite();
        $result = false;

        switch ($this->getQueryBuild()->getAction()) {
            case 'INSERT' :
                $inserts = $this->getQueryBuild()->getData();
                foreach ($inserts as $data) {
                    $bulkWrite->insert($data);
                }
                unset($data);

                $result = $this->getModel()
                    ->getWriteConnection()
                    ->executeBulkWrite(
                        $this->getModel()->getDatabase().'.'.$this->getModel()->getTable(),
                        $bulkWrite
                    )->getInsertedCount();

                $result = $result > 0
                    ? count($inserts) === 1 ? strval($inserts[0]['_id']) : array_map('strval', array_column($inserts, '_id'))
                    : false;
                break;

            case 'UPDATE' :
                $bulkWrite->update(
                    $this->getWhere(),
                    ['$set' => $this->queryBuild->getData()],
                    ['multi' => true]
                );

                $result = $this->getModel()
                    ->getWriteConnection()
                    ->executeBulkWrite(
                        $this->getModel()->getDatabase().'.'.$this->getModel()->getTable(),
                        $bulkWrite
                    )->getModifiedCount();

                $result = $result > 0 ? $result : false;
                break;

            case 'DELETE' :
                $bulkWrite->delete(
                    $this->getWhere(),
                    ['limit' => false]
                );

                $result = $this->getModel()
                    ->getWriteConnection()
                    ->executeBulkWrite(
                        $this->getModel()->getDatabase().'.'.$this->getModel()->getTable(),
                        $bulkWrite
                    )->getDeletedCount();

                $result = $result > 0 ? $result : false;
                break;
        }

        return $result;
    }

    public function getModel() : Model
    {
        return $this->model;
    }

    public function setModel(Model $model) : Process
    {
        $this->model = $model;

        return $this;
    }

    public function setQueryBuild(QueryBuild $queryBuild) : Process
    {
        $this->queryBuild = $queryBuild;

        return $this;
    }

    public function getQueryBuild() : QueryBuild
    {
        return $this->queryBuild;
    }

    private function getWhere()
    {
        return $this->getQueryBuild()->getWhere();
    }

    private function getOptions()
    {
        $options = [];
        if (!is_null($this->getQueryBuild()->getField())) {
            $options['projection'] = $this->getQueryBuild()->getField();
        }

        if (!is_null($this->getQueryBuild()->getOffset())) {
            $options['skip'] = $this->getQueryBuild()->getOffset();
        }

        if (!is_null($this->getQueryBuild()->getLimit())) {
            $options['limit'] = $this->getQueryBuild()->getLimit();
        }

        if (count($this->getQueryBuild()->getOrder()) > 0) {
            $options['sort'] = $this->getQueryBuild()->getOrder();
        }

        return $options;
    }

    private function stdToArray($result)
    {
        $array = [];

        foreach ($result as $key => $value) {
            if (is_object($value)) {
                switch (get_class($value)) {
                    case 'stdClass' :
                        $value = $this->{__FUNCTION__}($value);
                        break;

                    case 'MongoDB\BSON\ObjectId' :
                        $value = strval($value);
                        break;

                    case 'MongoDB\BSON\Timestamp' :
                        $time = strval($value);
                        $value = intval(substr($time, strpos($time, ':')  +1, -1));
                        break;

                    case 'MongoDB\BSON\UTCDateTime' :
                        $value = strval($value);
                        break;

                    case 'MongoDB\BSON\Binary' :
                        /**@var $value Binary**/
                        $value = $value->getData();
                        break;
                }
            } elseif (is_array($value)) {
                $value = $this->{__FUNCTION__}($value);
            }

            $array[$key] = $value;
        }

        return $array;
    }
}