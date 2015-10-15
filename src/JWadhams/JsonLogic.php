<?php 

namespace JWadhams;

class JsonLogic
{

	public static function is_logic($array) {
		return (
			is_array($array)
			and
			count($array) > 0
			and
			is_string(array_keys($array)[0])
		);
	}

    public static function apply($logic = [], $data = [])
    {
		if(is_object($logic)) $logic = (array)$logic;//I'd rather work with array syntax
		if(! self::is_logic($logic) ) return $logic;

		$operators = [
			'==' => function($a, $b){ return $a == $b; },
			'===' => function($a, $b){ return $a === $b; },
			'!=' => function($a, $b){ return $a != $b; },
			'!==' => function($a, $b){ return $a !== $b; },
			'>' => function($a, $b){ return $a > $b; },
			'>=' => function($a, $b){ return $a >= $b; },
			'<' => function($a, $b){ return $a < $b; },
			'<=' => function($a, $b){ return $a <= $b; },
			'!' => function($a){ return !$a; },
			'and' => function($a, $b){ return $a and $b; },
			'or' => function($a, $b){ return $a or $b; },
			'?:' => function($a, $b, $c){ return $a ? $b : $c; },
			'log' => function($a){ error_log($a); return $a; },
			'var' => function($a) use ($data){ 
				//Numeric index of array data, don't let explode() cast you
				if(is_numeric($a)) return $data[$a];

				//Descending into data using dot-notation
				foreach(explode('.', $a) as $prop){
					if(!isset($data[$prop])) return null; //Not found
					$data = $data[$prop];
				}
				return $data;
			}
		];

		//There can be only one operand per logic step
		$op = array_keys($logic)[0];
		$values = $logic[$op];

		//easy syntax for unary operators, like ["var" => "x"] instead of strict ["var" => ["x"]]
		$values = (array)$values; 
	
		//Recursion!
		$values = array_map(function($value) use ($data){
			return self::apply($value, $data);
		}, $values);

		if(!isset($operators[$op])){
			throw new \Exception("Unrecognized operator $op");
		}

		return call_user_func_array($operators[$op], $values);
    }
}

