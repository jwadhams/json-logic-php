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
		//I'd rather work with array syntax
		if(is_object($logic)) $logic = (array)$logic;
		if(is_object($data)) $data = (array)$data;

		if(! self::is_logic($logic) ) return $logic;

		$operators = [
			'==' => function($a, $b){ return $a == $b; },
			'===' => function($a, $b){ return $a === $b; },
			'!=' => function($a, $b){ return $a != $b; },
			'!==' => function($a, $b){ return $a !== $b; },
			'>' => function($a, $b){ return $a > $b; },
			'>=' => function($a, $b){ return $a >= $b; },
			'<' => function($a, $b, $c = null){ 
				return ($c === null) ? 
					( $a < $b ) : 
					( ( $a < $b ) and ( $b < $c ) ) ;
			},
			'<=' => function($a, $b, $c = null){ 
				return ($c === null) ? 
					( $a <= $b ) :
					( ( $a <= $b ) and ( $b <= $c ) ) ;
			},
			'%' => function($a, $b){ return $a % $b; },
			'!' => function($a){ return !$a; },
			'and' => function(){
				foreach(func_get_args() as $a){ if( ! $a) return $a; }
				return $a;
			},
			'or' => function(){
				foreach(func_get_args() as $a){ if($a) return $a; }
				return $a;
			},
			'?:' => function($a, $b, $c){ return $a ? $b : $c; },
			'log' => function($a){ error_log($a); return $a; },
			'var' => function($a) use ($data){ 
				//Descending into data using dot-notation
				//This is actually safe for integer indexes, PHP treats $a["1"] exactly like $a[1]
				foreach(explode('.', $a) as $prop){
					if(is_array($data)){
						if(!isset($data[$prop])) return null; //Not found
						$data = $data[$prop];
					}elseif(is_object($data)){
						if(!property_exists($data,$prop)) return null; //Not found
						$data = $data->{$prop};
					}else{
						return null;
					}
				}
				return $data;
			},
			'in' => function($a, $b){
				if(is_array($b)) return in_array($a, $b);
				if(is_string($b)) return strpos($b, $a) !== false;
				return false;
			},
			'cat' => function(){
				return implode(func_get_args(), "");
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

