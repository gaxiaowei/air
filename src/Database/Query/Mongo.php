<?php
namespace Air\Database\Query;

use Air\Database\Query;
use Air\Database\Query\Mongo\Parser;
use Air\Database\Query\Mongo\Tokenizer;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;

class Mongo implements Query
{
    use QueryTrait;

	private $where = [];
	private $bind = [];
	private $group = [];
	private $having = [];

    public function insert(array $data)
	{
        if (!isset($data['_id'])) {
            $data['_id'] = new ObjectID;
        } elseif (is_string($data['_id']) && strlen($data['_id']) === 24) {
            $data['_id'] = new ObjectID($data['_id']);
        }

        $bulkWrite = new BulkWrite();
        $bulkWrite->insert($data);

        $this->model->getWriteConnection()->executeBulkWrite(
            $this->model->getDatabase().'.'.$this->model->getTable(),
            $bulkWrite
        );

        return $data['_id'];
	}

	public function update(array $data)
	{
		if (isset($data['_id'])) {
			$key = $data['_id'];
			unset($data['_id']);

			return $this->where('_id = ?', [$key]);
		} else {
			$this->data  = $data;

			return $this;
		}
	}

	public function delete($data = null)
	{
        if (is_array($data)) {
            $this->where('_id in ?', [$data]);
        } elseif (is_string($data)) {
            $this->where('_id = ?', [$data]);
        }

		return $this;
	}

	public function select(string $columns = '*')
	{
		if (strpos($columns, '(') !== false &&
            preg_match_all('/(?:,|^|\s)(avg|count|max|min|sum|distinct)\s*\(([^\(\)]+)\)\s*(?:as\s+([a-z0-9_]+))?/i', $columns, $matches)
        ) {
			$aggregate = [];

			foreach ($matches[1] as $key => $function) {
                $as = empty($matches[3][$key]) ? $function : $matches[3][$key];
				$field = $matches[2][$key];

				if ($function === 'count') {
					$aggregate[$as] = ['$sum' => 1];
				} elseif ($function === 'distinct') {
					$aggregate[$as]  = ['$sum' => 1];

					$this->group    = [$field => '$'.$field];
					$this->distinct = $field;
				} else {
					$aggregate[$as]  = ["\${$function}"=>"\${$field}"];
				}
			}

			$this->aggregate = $aggregate;
		} else {
			if ($columns !== '*') {
				$fields = explode(',', strtr($columns, [' ' => '']));

				$this->columns = [];
				foreach($fields as $field) {
					$this->columns[$field] = true;
				}
			}
		}

		return $this;
	}

	public function where($condition, $bind) : Query
	{
		$bind  = is_null($bind) ? [] : $bind;
		$where = static::_condition($condition, $bind);

		$this->condition = $where['condition'];
		$this->bind = $where['bind'];

		return $this;
	}

	public function group(string $fields)
	{
		$group  = [];
		$fields = explode(',', strtr($fields, [' ' => '']));

		foreach ($fields as $field) {
			$group[$field] = '$'.$field;
		}

		$this->group = $group;

		return $this;
	}

	public function having(string $condition, $bind)
	{
		$where = static::_condition($condition, $bind);

		$this->having = $where['condition'];
		$this->bind = is_null($bind) ? $this->bind : array_merge($this->bind, $bind);

		return $this;
	}

	public function get(array $column = ['*'])
    {

    }

    public function find($id, array $column = ['*'])
    {

    }

    public function first(array $column = ['*'])
    {

    }

    private function parse($condition)
	{
		$tokens = Tokenizer::tokenize($condition);
		$tree = Parser::parse($tokens);

		return $tree;
	}

    private function condition($condition, array $bind)
	{
		switch (gettype($condition)) {
			case 'string' :
					if (!ctype_alnum($condition)) {
						break;
					}
			case 'integer':
					$bind = [$condition];
					$condition = '_id = ?';
					break;
			case 'array' :
					$bind = [$condition];
					$condition = '_id in(?)';
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
			} elseif ($key==='$exists') {
				continue;
			} else {
				throw new \LogicException('syntax error');
			}
		}

		return $tree;
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