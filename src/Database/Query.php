<?php
namespace Air\Database;

interface Query
{
	public function insert(array $data);
	public function update(array $data);
	public function delete($data);
	public function select(string $columns = '*');
	public function where($condition, $bind);
	public function group(string $fields);
	public function having(string $condition, $bind);
	public function order(string $field, string $direction = 'ASC');
	public function orderAsc(string $field);
	public function orderDesc(string $field);
	public function limit(int $limit);
	public function offset(int $offset);
	public function get(array $column = ['*']);
	public function find($id, array $column = ['*']);
	public function first(array $column = ['*']);
}