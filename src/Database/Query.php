<?php
namespace Air\Database;

interface Query
{
	public function insert(array $data) : Query;
	public function update(array $data) : Query;
	public function delete(array $data = null) : Query;
	public function select(string $columns = '*') : Query;
	public function where($condition, array $bind = null) : Query;
	public function group(string $fields) : Query;
	public function having($condition, array $bind = null) : Query;
	public function order(string $field, string $direction = 'ASC') : Query;
	public function orderAsc(string $field) : Query;
	public function orderDesc(string $field) : Query;
	public function limit(int $limit) : Query;
	public function take(int $offset) : Query;
	public function skip(int $offset) : Query;
	public function offset(int $offset) : Query;

	public function QueryBuild() : QueryBuild;
}