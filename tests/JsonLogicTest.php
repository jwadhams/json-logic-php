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

}
