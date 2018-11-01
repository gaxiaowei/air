<?php
namespace Air\Database\Query;

use Air\Database\Query;
use Air\Database\Query\Mongo\Parser;
use Air\Database\Query\Mongo\Tokenizer;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\BulkWrite;

class Mongo extends QueryCommon implements Query
{
    public function insert(array $data)
	{
        if ($this->step >= 1) {
            throw new \LogicException('syntax error');
        }

        if (!isset($data['_id'])) {
            $data['_id'] = new ObjectID;
        } elseif (is_string($data['_id']) && strlen($data['_id']) === 24) {
            $data['_id'] = new ObjectID($data['_id']);
        }

        $this->type = static::INSERT;
        $this->data = $data;
        $this->step= 7;

        return $this;
	}

	public function update(array $data)
	{
        if ($this->step >= 1) {
            throw new \LogicException('syntax error');
        }

        $this->type = static::UPDATE;

        if (isset($data['_id'])) {
            $key = $data['_id'];
            unset($data['_id']);

            $this->data = $data;

            return $this->where('_id = ?', [$key]);
        } else {
            $this->data = $data;
            $this->step = 2;

            return $this;
        }
	}

	public function delete($data = null)
	{
        if ($this->step >= 1) {
            throw new \LogicException('syntax error');
        }

        $this->type = static::DELETE;

        if (isset($data['_id'])) {
            return $this->where('_id = ?', [$data['_id']]);
        }

        $this->step = 2;

        return $this;
	}

	public function select(string $columns = '*') : Query
    {
        if ($this->step >= 1) {
            throw new \LogicException('syntax error', 2001);
        }

        if ($columns !== '*') {
            $fields = explode(',', strtr($columns, ' ', ''));
            $this->select = [];

            foreach($fields as $field) {
                $this->select[$field] = 1;
            }
        }

        $this->step = 1;

        return $this;
    }

    public function where($condition, array $bind = null) : Query
    {
        if ($this->step >= 3) {
            throw new \LogicException('syntax error');
        }

        $bind = is_null($bind) ? [] : $bind;
        $where = static::condition($condition, $bind);
        $this->where = $where['condition'];
        $this->whereParameters = $where['bind'];

        $this->step = 3;

        return $this;
    }

	public function group(string $fields) : Query
	{
        if ($this->step >= 4) {
            throw new \LogicException('syntax error');
        }

        $group = [];
        $fields = explode(',', strtr($fields, ' ', ''));

        foreach ($fields as $field) {
            $group[$field] = '$'.$field;
        }

        $this->groupBy = $group;
        $this->step = 4;

        return $this;
	}

	public function having($condition, array $bind = null) : Query
	{
        if ($this->step != 4) {
            throw new \LogicException('syntax error');
        }

        $where = static::condition($condition, $bind);
        $this->having = $where['condition'];
        $this->havingParameters = is_null($bind) ? [] : $bind;

        $this->step = 5;

        return $this;
	}

	public function fetch()
    {
        return $this->skip(0)->take(1)->fetchAll();
    }

    public function fetchAll()
    {
        $tree = $this->parse($this->where);
        $where = $this->bind($tree, $this->whereParameters);
        $result = [];

        $query  = new \MongoDB\Driver\Query($where, [
            'projection' => $this->select,
            'skip' => $this->offset,
            'limit' => $this->limit,
            'sort' => $this->order
        ]);
        $cursor = $this->getModel()
            ->getReadConnection()
            ->executeQuery(
                $this->getModel()->getDatabase().'.'.$this->getModel()->getTable(),
                $query
            )->toArray();

        foreach ($cursor as $row) {
            $row = (array)$row;

            if (isset($row['_id'])) {
                $row['_id'] = strval($row['_id']);
            }

            $result[] = $this->stdToArray($row);
        }

        return $result;
    }

    public function execute()
    {
        $tree = $this->parse($this->where);
        $where = $this->bind($tree, $this->whereParameters);
        $bulkWrite = new BulkWrite();
        $result = false;

        switch ($this->type) {
            case static::INSERT :
                $bulkWrite->insert($this->data);

                $result = $this->getModel()
                    ->getWriteConnection()
                    ->executeBulkWrite(
                        $this->getModel()->getDatabase().'.'.$this->getModel()->getTable(),
                        $bulkWrite
                    )->getInsertedCount();

                $result = $result > 0 ? $this->data['_id'] : false;
                break;

            case static::UPDATE :
                $bulkWrite->update(
                    $where,
                    ['$set' => $this->data],
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

            case static::DELETE :
                $bulkWrite->delete(
                    $where,
                    ['limit' => 0]
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

    private function parse($condition)
	{
	    if (!$condition) {
	        return [];
        }

		$tokens = Tokenizer::tokenize($condition);
		$tree = Parser::parse($tokens);

		return $tree;
	}

    private function bind(array &$tree, array &$bind = null)
	{
		foreach ($tree as $key => $condition) {
			if ($key === '_id') {
				$value = array_shift($bind);

				if (!is_array($value)) {
					$value = [$value];
				}

                foreach ($value as $index => $id) {
                    if(is_string($id) && strlen($id) === 24) {
                        $value[$index] = new ObjectID($id);
                    }
                }
                unset($index, $id);

				array_unshift($bind, $value);
			}

			if (is_array($condition)) {
				$tree[$key] = $this->{__FUNCTION__}($condition, $bind);
			} elseif ($condition === '?') {
				$value = array_shift($bind);

				if ($value === null) {
				    throw new \InvalidArgumentException('SQL parameter is missing');
				}

				if ($key === '$like') {
					unset($tree[$key]);

					$head   = substr($value, 0, 1);
					$tail   = substr($value, -1);
					$middle = substr($value, 1, -1);

					$head   = $head === '%' ? '' : '^'.$head;
					$tail   = $tail === '%' ? '' : $tail.'$';
					$middle = str_replace('%', '.+', $middle);
					$middle = str_replace('_', '.', $middle);

					$value  = $head.$middle.$tail;

					$key = '$regex';
                    $tree['$options'] = 'i';
				} elseif ($key === '$near') {
					if (!is_array($value) && count($value) > 1) {
						throw new \LogicException('syntax error');
					}

					$lng = floatval(array_shift($value));
					$lat = floatval(array_shift($value));
					$distance = count($value) === 0 ? 2000 : intval(array_shift($value));

					$value = [
						'$geometry' => [
							'type' => 'Point',
							'coordinates' => [$lng, $lat],
						],
						'$maxDistance'=> $distance,
					];
				}

				$tree[$key] = $value;
			} elseif ($key === '$exists') {
				continue;
			} else {
				throw new \LogicException('syntax error');
			}
		}

		return $tree;
	}

    private function condition($condition, array $bind)
    {
        switch (gettype($condition)) {
            case 'string' :
                if (!ctype_alnum($condition)) {
                    break;
                }
            case 'integer' :
                $bind = [$condition];
                $condition = '_id = ?';
                break;

            case 'array' :
                $bind = [$condition];
                $condition = '_id in (?)';
                break;

            default :
                throw new \LogicException('syntax error');
                break;
        }

        return [
            'condition' => $condition,
            'bind' => $bind
        ];
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

					case 'MongoDB\BSON\ObjectID' :
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