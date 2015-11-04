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
        $this->assertEquals($expected, JWadhams\JsonLogic::apply($logic, $data));
    }


    public function commonProvider()
    {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, 'http://jsonlogic.com/tests.json');
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		
		$body = curl_exec($c);

		$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		
		if($code !== 200){
			die("Could not download shared tests from jsonlogic.com: {$code} \n");
		}

		curl_close($c);
		
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
