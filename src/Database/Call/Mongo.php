<?php
namespace Air\Database\Call;

use Air\Database\ICall;
use Air\Database\IConnection;
use Air\Database\Query\IBuilder;
use MongoDB\BSON\Binary;
use MongoDB\Driver\BulkWrite;

class Mongo implements ICall
{
    /**@var $model IConnection*/
    private $connection;

    /**@var $query IBuilder**/
    private $builder;

    public function __construct(IConnection $connection = null, IBuilder $builder = null)
    {
        $this->connection = $connection;
        $this->builder = $builder;
    }

    public function fetch()
    {
        $this->getBuilder()->setAttribute('limit', 0);
        $this->getBuilder()->setAttribute('offset', 1);

        return $this->fetchAll()[0] ?? [];
    }

    public function fetchAll()
    {
        $result = [];

        $query  = new \MongoDB\Driver\Query(
            $this->getWhere(),
            $this->getOptions()
        );

        $cursor = $this->getConnection()
            ->executeQuery(
                $this->getBuilder()->getDatabase().'.'.$this->getBuilder()->getTable(),
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

        switch ($this->getBuilder()->getAction()) {
            case 'INSERT' :
                $inserts = $this->getBuilder()->getData();
                foreach ($inserts as $data) {
                    $bulkWrite->insert($data);
                }
                unset($data);

                $result = $this->getConnection()
                    ->executeBulkWrite(
                        $this->getBuilder()->getDatabase().'.'.$this->getBuilder()->getTable(),
                        $bulkWrite
                    )->getInsertedCount();

                $result = $result > 0
                    ? count($inserts) === 1 ? strval($inserts[0]['_id']) : array_map('strval', array_column($inserts, '_id'))
                    : false;
                break;

            case 'UPDATE' :
                $bulkWrite->update(
                    $this->getWhere(),
                    ['$set' => $this->getBuilder()->getData()],
                    ['multi' => true]
                );

                $result = $this->getConnection()
                    ->executeBulkWrite(
                        $this->getBuilder()->getDatabase().'.'.$this->getBuilder()->getTable(),
                        $bulkWrite
                    )->getModifiedCount();

                $result = $result > 0 ? $result : false;
                break;

            case 'DELETE' :
                $bulkWrite->delete(
                    $this->getWhere(),
                    ['limit' => false]
                );

                $result = $this->getConnection()
                    ->executeBulkWrite(
                        $this->getBuilder()->getDatabase().'.'.$this->getBuilder()->getTable(),
                        $bulkWrite
                    )->getDeletedCount();

                $result = $result > 0 ? $result : false;
                break;
        }

        return $result;
    }

    public function getConnection() : IConnection
    {
        return $this->connection;
    }

    public function setConnection(IConnection $connection) : ICall
    {
        $this->connection = $connection;

        return $this;
    }

    public function setBuilder(IBuilder $builder) : ICall
    {
        $this->builder = $builder;

        return $this;
    }

    public function getBuilder() : IBuilder
    {
        return $this->builder;
    }

    private function getWhere()
    {
        return $this->getBuilder()->getWhere();
    }

    private function getOptions()
    {
        $options = [];
        if (!is_null($this->getBuilder()->getField())) {
            $options['projection'] = $this->getBuilder()->getField();
        }

        if (!is_null($this->getBuilder()->getOffset())) {
            $options['skip'] = $this->getBuilder()->getOffset();
        }

        if (!is_null($this->getBuilder()->getLimit())) {
            $options['limit'] = $this->getBuilder()->getLimit();
        }

        if (count($this->getBuilder()->getOrder()) > 0) {
            $options['sort'] = $this->getBuilder()->getOrder();
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