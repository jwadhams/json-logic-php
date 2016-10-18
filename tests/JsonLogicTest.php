<?php

class JsonLogicTest extends PHPUnit_Framework_TestCase{

	/**
	 * @expectedException Exception
	 */
	public function testInvalidOperator()
	{
		JWadhams\JsonLogic::apply(['fubar'=> [1,2]]);
	}

	/**
	 * @dataProvider commonProvider
	 */
	public function testCommon($logic, $data, $expected)
	{
		// Assert
		$this->assertEquals(
			$expected,
			JWadhams\JsonLogic::apply($logic, $data),
			"JsonLogic::apply(".json_encode($logic).", ".json_encode($data).") == ".json_encode($expected)
		);
	}


	public function commonProvider()
	{
		$local_path = __DIR__ . '/tests.json';

		if( ! file_exists($local_path)){
			echo "Downloading shared tests from JsonLogic.com ...\n";
			file_put_contents($local_path, fopen("http://jsonlogic.com/tests.json", 'r'));
		}else{
			echo "Using cached tests from " . @ date('r', filemtime($local_path)) ."\n";
			echo "(rm {$local_path} to refresh)\n";
		}

		$body = file_get_contents($local_path);

		//Every scenario is double tested
		$common_tests = array_merge(
				json_decode($body),//once using PHP objects
				json_decode($body, true)//once using PHP associative arrays
				);
		$common_tests = array_filter($common_tests, function($row){
			//Discard comments or malformed rows
			return is_array($row) and count($row) >= 3;
		});

		return $common_tests;
	}

	public function patternProvider(){
		$local_path = __DIR__ . '/patterns.json';
		$patterns = json_decode(file_get_contents($local_path), true);
		$patterns = array_filter($patterns, function($row){
			//Discard comments or malformed rows
			return is_array($row) and count($row) == 3;
		});
		return $patterns;
	}
	/**
	 * @dataProvider patternProvider
	 */
	public function testPattern($pattern, $rule, $expected)
	{
		// Assert
		$this->assertEquals(
			$expected,
			JWadhams\JsonLogic::rule_like($rule, $pattern),
			"JsonLogic::rule_like(".json_encode($rule).", ".json_encode($pattern).") == ".json_encode($expected)
		);
	}

	/* Snappy way to test just one rule when you need to pepper in some echos into the code*/
	public function testProblematicPattern(){
		$raw = <<<JSON
		[
			{"*":["number", {"+":"array"}]},
			{"*" : [0.01, {"+":[{"var":"goods"}, {"var":"services"}]}]},
			true
		],


JSON;
		$raw = preg_replace('#,$#', '', trim($raw) );//Drop trailing commas

		if(!json_decode($raw)){
			throw new Exception("Couldn't parse problematic pattern");
		}
		list($pattern, $rule, $expected) = json_decode($raw, true);
		$this->assertEquals(
			$expected,
			JWadhams\JsonLogic::rule_like($rule, $pattern),
			"JsonLogic::rule_like(".json_encode($rule).", ".json_encode($pattern).") == ".json_encode($expected)
		);

	}


}
