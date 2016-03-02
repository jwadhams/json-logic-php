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

	public static function truthy($logic){
		if($logic === "0") return true;
		return (bool)$logic;
	}

	public static function apply($logic = [], $data = []) {
		//I'd rather work with array syntax
		if(is_object($logic)) $logic = (array)$logic;
		if(is_object($data)) $data = (array)$data;

		if(! self::is_logic($logic) ){ return $logic; }

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
			'!!' => function($a){ return static::truthy($a); },
			'!' => function($a){ return ! static::truthy($a); },
			'and' => function(){
				foreach(func_get_args() as $a){ if( ! static::truthy($a) ) return $a; }
				return $a;
			},
			'or' => function(){
				foreach(func_get_args() as $a){ if( static::truthy($a) ) return $a; }
				return $a;
			},
			'log' => function($a){ error_log($a); return $a; },
			'var' => function($a, $default = null) use ($data){
				//Descending into data using dot-notation
				//This is actually safe for integer indexes, PHP treats $a["1"] exactly like $a[1]
				foreach(explode('.', $a) as $prop){
					if(is_array($data)){
						if(!isset($data[$prop])) return $default; //Not found
						$data = $data[$prop];
					}elseif(is_object($data)){
						if(!property_exists($data,$prop)) return $default; //Not found
						$data = $data->{$prop};
					}else{
						return $default; //Trying to get a value from a primitive
					}
				}
				return $data;
			},
			'missing' => function() use ($data){
				/*
					Missing can receive many keys as many arguments, like {"missing:[1,2]}
					Missing can also receive *one* argument that is an array of keys,
					which typically happens if it's actually acting on the output of another command
					(like IF or MERGE)
				*/
				$values = func_get_args();
				if(!static::is_logic($values) and isset($values[0]) and is_array($values[0] ) ){
					$values = $values[0];
				}

				$missing = [];
				foreach($values as $data_key){
					$value = static::apply(['var'=>$data_key], $data);
					if($value === null or $value === ""){
						array_push($missing, $data_key);
					}
				}

				return $missing;
			},
			'in' => function($a, $b){
				if(is_array($b)) return in_array($a, $b);
				if(is_string($b)) return strpos($b, $a) !== false;
				return false;
			},
			'cat' => function(){
				return implode(func_get_args(), "");
			},
			'max' => function(){ return max(func_get_args()); },
			'min' => function(){ return min(func_get_args()); },
			'+' => function(){ return array_sum(func_get_args()); },
			'-' => function($a,$b=null){ if($b===null){return -$a;}else{return $a - $b;} },
			'/' => function($a,$b){ return $a / $b; },
			'*' => function(){
				return array_reduce(func_get_args(), function($a, $b){ return $a*$b; }, 1);
			},
			'merge' => function(){
				return array_reduce(func_get_args(), function($a, $b){
					return array_merge((array)$a, (array)$b);
				}, []);
			}
		];

		//There can be only one operand per logic step
		$op = array_keys($logic)[0];
		$values = $logic[$op];

		//easy syntax for unary operators, like ["var" => "x"] instead of strict ["var" => ["x"]]
		if(!is_array($values) or static::is_logic($values)){
			$values = [ $values ];
		}

		// 'if' violates the normal rule of depth-first calculating all the values,
		//let it manage its own recusrion
		if($op === 'if' || $op == '?:'){
			/* 'if' should be called with a odd number of parameters, 3 or greater
				This works on the pattern:
				if( 0 ){ 1 }else{ 2 };
				if( 0 ){ 1 }else if( 2 ){ 3 }else{ 4 };
				if( 0 ){ 1 }else if( 2 ){ 3 }else if( 4 ){ 5 }else{ 6 };

				The implementation is:
				For pairs of values (0,1 then 2,3 then 4,5 etc)
					If the first evaluates truthy, evaluate and return the second
					If the first evaluates falsy, jump to the next pair (e.g, 0,1 to 2,3)
				given one parameter, evaluate and return it. (it's an Else and all the If/ElseIf were false)
				given 0 parameters, return NULL (not great practice, but there was no Else)
			*/
			for($i = 0 ; $i < count($values) - 1 ; $i += 2){
				if( static::truthy( static::apply($values[$i], $data) ) ){
					return static::apply($values[$i+1], $data);
				}
			}
			if(count($values) === $i+1) return static::apply($values[$i], $data);
			return null;
		}


		//Recursion!
		$values = array_map(function($value) use ($data){
			return self::apply($value, $data);
		}, $values);

		if(!isset($operators[$op])){
			throw new \Exception("Unrecognized operator $op");
		}

		return call_user_func_array($operators[$op], $values);
	}

	public static function uses_data($logic){
		if(is_object($logic)) $logic = (array)$logic;
		$collection = [];

		if( self::is_logic($logic) ){
			$op = array_keys($logic)[0];
			$values = (array)$logic[$op];

			if($op === "var"){
				//This doesn't cover the case where the arg to var is itself a rule.
				$collection[] = $values[0];
			}else{
				//Recursion!
				foreach($values as $value){
					$collection = array_merge($collection, self::uses_data($value));
				}
			}
		}

		return array_unique($collection);
	}
}
