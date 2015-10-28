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
		
		$common_handle = fopen(__DIR__ . "/tests.csv", "r");
		if(!$common_handle) die("Couldn't open common tests");
		
		$common_tests = [];
		while( false !== ( $row = fgetcsv($common_handle)  )){
			if($row and count($row) >= 3){
				//Every scenario is double testsed, once using PHP associative arrays:
				$common_tests[] = array_map('json_decode', $row, array_fill(0, count($row), true));
				//Every scenario is double testsed, once using PHP objects:
				$common_tests[] = array_map('json_decode', $row);
			}
		}
		fclose($common_handle);
		return $common_tests;
	}


}
