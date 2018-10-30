<?php
namespace Air\Database\Query\Mongo;

class Parser
{
	private static $index  = 0;
	private static $length = 0;
	private static $tokens = [];

	public static function parse(array $tokens)
	{
		static::$index  = 0;
		static::$length = count($tokens);
		static::$tokens = $tokens;

		$tree = static::tree();
		if (!isset($tree['conditions'][0])) {
		    throw new \InvalidArgumentException('syntax error');
		}

		return count(current($tree['conditions'][0])) === 1 ? current(current($tree['conditions'][0])) : $tree['conditions'][0];
	}

	private static function tree()
	{
        $state = 0;
        $logical = '$and';
        $conditions = [];

		for (; static::$index < static::$length; static::$index++) {
			$token = static::$tokens[static::$index];

			switch($state) {
				case 0 :
					switch($token) {
						case '(' :
							static::$index++;

							$child = static::tree();
                            $conditions[][$child['logical']] = $child['conditions'];
							$state = 2;
						break;

						default :
							$key = $token;
						break;
					}
				break;

				case 1 :
					switch (strtolower($token)) {
						case '=' :
							$operate = '$eq';
						break;

						case '!=' :
                            $operate = '$ne';
						break;

						case '>' :
                            $operate = '$gt';
						break;

						case '>=' :
                            $operate = '$gte';
						break;

						case '<' :
                            $operate = '$lt';
						break;

						case '<=' :
                            $operate = '$lte';
						break;

						case 'like' :
                            $operate = '$like';
						break;

						case 'regexp' :
                            $operate = '$regex';
						break;

						case 'near' :
                            $operate = '$near';
						break;

						case 'in' :
							if (isset($operate) && $operate === 'not') {
                                $operate = '$nin';
							} else {
                                $operate = '$in';
							}
						break;

						case 'is' :
                            $operate = 'is';

							$state--;
						break;

						case 'not' :
							if (isset($operate) && $operate === 'is') {
                                $operate = 'is not';
							} else {
                                $operate = 'not';
							}

							$state--;
						break;

						case 'null' :
							static::$index--;
							if (isset($operate) && $operate === 'is not') {
								$value = 1;
							} else {
								$value = 0;
							}

                            $operate = '$exists';
						break;

						default :
							throw new \InvalidArgumentException('syntax error');
						break;
					}
				break;

				case 2 :
					switch (strtolower($token)) {
						case 'null' :
						break;

						case '?' :
							$value = $token;
						break;

						default :
							throw new \InvalidArgumentException('syntax error');
						break;
					}

                    $conditions[] = $operate === '$eq' ? [$key => $value] : [$key => [$operate => $value]];
				break;

				case 3 :
					switch (strtolower($token)) {
						case ')' :
							return [
							    'logical' => $logical,
                                'conditions' => $conditions
                            ];
						break;

						case 'and' :
							$logical = '$and';
							$state   = -1;
						break;

						case 'or' :
							$logical = '$or';
							$state   = -1;
						break;

						default :
							throw new \InvalidArgumentException('syntax error');
						break;
					}
				break;

				default :
					throw new \InvalidArgumentException('syntax error');
				break;
			}

			$state++;
		}

		return [
		    'logical' => $logical,
            'conditions' => $conditions
        ];
	}
}