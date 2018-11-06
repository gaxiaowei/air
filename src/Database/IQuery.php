<?php
namespace Air\Database;

use Air\Database\Query\IBuilder;

interface IQuery
{
	public function insert(array $data) : IQuery;
	public function update(array $data) : IQuery;
	public function delete(array $data = null) : IQuery;
	public function select(string $columns = '*') : IQuery;
	public function where($condition, array $bind = null) : IQuery;
	public function group(string $fields) : IQuery;
	public function having($condition, array $bind = null) : IQuery;
	public function order(string $field, string $direction = 'ASC') : IQuery;
	public function orderAsc(string $field) : IQuery;
	public function orderDesc(string $field) : IQuery;
	public function limit(int $limit) : IQuery;
	public function take(int $offset) : IQuery;
	public function skip(int $offset) : IQuery;
	public function offset(int $offset) : IQuery;

	public function setDatabase(string $database) : IQuery;
	public function setTable(string $table) : IQuery;
    public function setKey(string $key) : IQuery;

	public function build() : IBuilder;
}