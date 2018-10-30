<?php
namespace Air\Database;

interface Query
{
	public function insert(array $data) : Query;
	public function update(array $data) : Query;
	public function delete($data) : Query;
	public function select(string $columns = '*') : Query;
	public function where($condition, $bind) : Query;
	public function group(string $fields) : Query;
	public function having(string $condition, $bind) : Query;
	public function order(string $field, string $direction = 'ASC') : Query;
	public function orderAsc(string $field) : Query;
	public function orderDesc(string $field) : Query;
	public function limit(int $limit) : Query;
	public function offset(int $offset) : Query;
	public function get(array $column = ['*']);
	public function find($id, array $column = ['*']);
	public function first(array $column = ['*']);
}