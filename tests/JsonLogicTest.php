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
     * @dataProvider csvProvider
     */
	public function testCommon($logic, $data, $expected)
    {
        // Assert
        $this->assertEquals($expected, JWadhams\JsonLogic::apply($logic, $data));
    }


    public function csvProvider()
    {
		ini_set('auto_detect_line_endings',TRUE);
		
		$common_handle = fopen("php://temp", "rw");
		
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, 'http://jsonlogic.com/tests.csv');
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		
		fwrite($common_handle, curl_exec($c));

		$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		
		if($code !== 200){
			die("Could not download shared tests from jsonlogic.com: {$code} \n");
		}
		curl_close($c);
		rewind($common_handle);
		
		/*
		$common_handle = fopen(__DIR__ . "/tests.csv", "r");
		if(!$common_handle) die("Couldn't open common tests");
		*/
		
		$common_tests = [];
		while( false !== ( $row = fgetcsv($common_handle)  )){
			if($row and count($row) >= 3){
				//Every scenario is double tested
				//once using PHP objects:
				$common_tests[] = array_map('json_decode', $row);
				//once using PHP associative arrays:
				$common_tests[] = array_map('json_decode', $row, array_fill(0, count($row), true));
			}
		}
		fclose($common_handle);
		return $common_tests;
	}


}
