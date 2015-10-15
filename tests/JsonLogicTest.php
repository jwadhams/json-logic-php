<?php

class JsonLogicTest extends PHPUnit_Framework_TestCase{

	/**
     * @expectedException Exception
     */
    public function testInvalidOperator()
    {
		JWadhams\JsonLogic::apply(['fubar'=> [1,2]]);
    }

	/*
	public function testLoggingOperator()
	{
		$string = 'Hello, World';
		$this->expectOutputString($string);	

		$passed = JWadhams\JsonLogic::apply(['log' => [$string]]);
        
		$this->assertEquals($passed, $string);
	}
	*/
	

	public function testHandlesObjectRules(){

		$object_style = json_decode('{"==":["apples", "apples"]}');
        $this->assertEquals(true, JWadhams\JsonLogic::apply($object_style));
    }

	/**
     * @dataProvider singleProvider
     */
	public function testSingleOperators($logic, $data, $expected)
    {
        // Assert
        $this->assertEquals($expected, JWadhams\JsonLogic::apply($logic, $data));
    }


    public function singleProvider()
    {
        return [
			[ ["==" => [1,1]], [], true ],
			[ ["==" => [1,"1"]], [], true ],
			[ ["==" => [1,2]], [], false ],

			[ ["===" => [1,1]], [], true ],
			[ ["===" => [1,"1"]], [], false ],
			[ ["===" => [1,2]], [], false ],

			[ [ "!=" => [1, 2] ], [], true ],
			[ [ "!=" => [1, 1] ], [], false ],

			[ [ "!==" => [1, 2] ], [], true ],
			[ [ "!==" => [1, 1] ], [], false ],
			[ [ "!==" => [1, "1"] ], [], true ],
			[ [ ">" => [2, 1] ], [], true ],
			[ [ ">" => [1, 1] ], [], false ],
			[ [ ">" => [1, 2] ], [], false ],
			[ [ ">" => ["2", 1] ], [], true ],

			[ [ ">=" => [2, 1] ], [], true ],
			[ [ ">=" => [1, 1] ], [], true ],
			[ [ ">=" => [1, 2] ], [], false ],
			[ [ ">=" => ["2", 1] ], [], true ],

			[ [ "<" => [2, 1] ], [], false ],
			[ [ "<" => [1, 1] ], [], false ],
			[ [ "<" => [1, 2] ], [], true ],
			[ [ "<" => ["1", 2] ], [], true ],

			[ [ "<=" => [2, 1] ], [], false ],
			[ [ "<=" => [1, 1] ], [], true ],
			[ [ "<=" => [1, 2] ], [], true ],
			[ [ "<=" => ["1", 2] ], [], true ],

			[ [ "!" => [false] ], [], true ],
			[ [ "!" => false ], [], true ],
			[ [ "!" => [true] ], [],false ],
			[ [ "!" => true ], [], false ],
			[ [ "!" => 0 ], [], true ],

			[ [ "or" => [true, true] ], [], true ],
			[ [ "or" => [false, true] ], [], true ],
			[ [ "or" => [true, false] ], [], true ],
			[ [ "or" => [false, false] ], [], false ],

			[ [ "and" => [true, true] ], [], true ],
			[ [ "and" => [false, true] ], [], false ],
			[ [ "and" => [true, false] ], [], false ],
			[ [ "and" => [false, false] ], [], false],

			[ [ "?:" => [true, 1, 2] ], [], 1 ],
			[ [ "?:" => [false, 1, 2] ], [], 2 ],
		];
    }


	/**
     * @dataProvider compoundProvider
     */
	public function testCompoundLogic($logic, $data, $expected)
    {
        // Assert
        $this->assertEquals($expected, JWadhams\JsonLogic::apply($logic, $data));
    }


    public function compoundProvider()
    {
        return [
			[ ['and'=>[ ['>' => [3,1]], true ]], [], true ],
			[ ['and'=>[ ['>' => [3,1]], false ]], [], false ],

			[ ['and'=>[ ['>' => [3,1]], ['!'=>true] ]], [], false ],

			[ ['and'=>[ ['>' => [3,1]], ['<'=>[1,3]] ]], [], true ],

			[ ['?:'=>[ ['>'=>[3,1]], 'visible', 'hidden' ]], [], 'visible']
		];
    }


	/**
     * @dataProvider dataDrivenProvider
     */
	public function testDataDriven($logic, $data, $expected)
    {
        // Assert
        $this->assertEquals($expected, JWadhams\JsonLogic::apply($logic, $data));
    }


    public function dataDrivenProvider()
    {
        return [
			[ ["var"=>["a"]], ["a"=>1], 1 ],
			[ ["var"=>["b"]], ["a"=>1], null ],
			[ ["var"=>["a"]], null, null ],
			[ ["var"=>"a"], ["a"=>1], 1 ],
			[ ["var"=>"b"], ["a"=>1], null ],
			[ ["var"=>"a"], null, null ],

			//Depth
			[ ["var"=>"a.b"], ["a"=>["b"=>"c"]], "c" ],
			[ ["var"=>"a.q"], ["a"=>["b"=>"c"]], null ],

			//Array
			[ ["var"=>1], ["apple","banana"], "banana" ],

			//Compound examples from the docs
			[
				[ "and"=> [
					[ "<"=> [[ "var"=>"temp" ], 110] ],
					[ "=="=> [ [ "var"=>"pie.filling" ], "apple" ] ] ] 
				],
				[ "temp" => 100, "pie" => [ "filling" => "apple" ] ],
				true
			],
			[
				[ "var" => [
					[ "?:" => [
						[ "<" => [[ "var"=>"temp" ], 110] ], "pie.filling", "pie.eta" 
					] ]
				]],
				[ "temp" => 100, "pie" => [ "filling" => "apple", "eta" => "60s" ] ],
				"apple"
			]
		];
    }

}
