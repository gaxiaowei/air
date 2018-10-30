<?php
namespace Air\Database\Query\Mongo;

class Tokenizer
{
	public static function tokenize($condition)
	{
		$tokens = static::scan($condition);
		$tokens = array_filter($tokens, [__CLASS__, 'filter']);
		$tokens = array_values($tokens);

		if (!isset($tokens[4])) {
		    throw new \InvalidArgumentException('syntax error');
		}

		return $tokens;
	}

	private static function scan($condition)
	{
		$condition = preg_replace('/\s*([=!><]+|\(|\)|\sand\s|\sor\s)\s*/i', ' $1 ', '('.$condition.')');
		$condition = preg_replace('/\sin\s*\(\s*\?\s*\)/i', ' in ?', $condition);

		return explode(' ', $condition);
	}

	private static function filter($element)
	{
		return $element !== '';
	}
}
