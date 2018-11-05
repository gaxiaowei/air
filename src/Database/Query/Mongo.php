<?php
namespace Air\Database\Query;

use Air\Database\Query;
use Air\Database\Query\Mongo\Parser;
use Air\Database\Query\Mongo\Tokenizer;
use MongoDB\BSON\ObjectId;

class Mongo extends QueryCommon implements Query
{
    public function insert(array $data) : Query
	{
        if ($this->step >= 1) {
            throw new \LogicException('syntax error');
        }

        if (count($data) === count($data, COUNT_RECURSIVE)) {
            $data = [$data];
        }

        foreach ($data as &$item) {
            if (!isset($item['_id'])) {
                $item['_id'] = new ObjectID;
            } elseif (is_string($item['_id']) && strlen($item['_id']) === 24) {
                $item['_id'] = new ObjectID($item['_id']);
            }
        }

        $this->type = strtoupper(__FUNCTION__);
        $this->data = $data;
        $this->step= 7;

        return $this;
	}

	public function update(array $data) : Query
	{
        if ($this->step >= 1) {
            throw new \LogicException('syntax error');
        }

        $this->type = strtoupper(__FUNCTION__);

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

	public function delete(array $data = null) : Query
	{
        if ($this->step >= 1) {
            throw new \LogicException('syntax error');
        }

        $this->type = strtoupper(__FUNCTION__);

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

	public function queryBuild() : \Air\Database\QueryBuild
    {
        $where = $this->parse($this->where);
        $having = $this->parse($this->having);

        $queryBuild = new QueryBuild();
        $queryBuild->setAttribute('field', $this->select);
        $queryBuild->setAttribute('where', $this->bind($where, $this->whereParameters));
        $queryBuild->setAttribute('group', $this->groupBy);
        $queryBuild->setAttribute('having', $this->bind($having, $this->havingParameters));
        $queryBuild->setAttribute('offset', $this->offset);
        $queryBuild->setAttribute('limit', $this->limit);
        $queryBuild->setAttribute('order', $this->order);

        $queryBuild->setAttribute('action', $this->type);
        $queryBuild->setAttribute('data', $this->data);

        return $queryBuild;
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
}