<?php

use PHPUnit\Framework\TestCase;

class JsonLogicTest extends TestCase
{
    public function testInvalidOperator()
    {
        $this->expectExceptionMessage('Unrecognized operator fubar');
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

        if (! file_exists($local_path)) {
            echo "Downloading shared apply() tests from JsonLogic.com ...\n";
            file_put_contents($local_path, fopen("http://jsonlogic.com/tests.json", 'r'));
        } else {
            echo "Using cached apply() tests from " . @ date('r', filemtime($local_path)) ."\n";
            echo "(rm {$local_path} to refresh)\n";
        }

        $body = file_get_contents($local_path);

        $test_as_objects = json_decode($body);
        $test_as_associative = json_decode($body, true);

        if ($test_as_objects === null or $test_as_associative === null) {
            die("Could not parse tests.json!");
        }

        //Every scenario is double tested
        $common_tests = array_merge(
                json_decode($body),//once using PHP objects
                json_decode($body, true)//once using PHP associative arrays
                );
        $common_tests = array_filter($common_tests, function ($row) {
            //Discard comments or malformed rows
            return is_array($row) and count($row) >= 3;
        });

        return $common_tests;
    }

    public function patternProvider()
    {
        $local_path = __DIR__ . '/rule_like.json';

        if (! file_exists($local_path)) {
            echo "Downloading shared rule_like() tests from JsonLogic.com ...\n";
            file_put_contents($local_path, fopen("http://jsonlogic.com/rule_like.json", 'r'));
        } else {
            echo "Using cached rule_like() tests from " . @ date('r', filemtime($local_path)) ."\n";
            echo "(rm {$local_path} to refresh)\n";
        }

        $patterns = json_decode(file_get_contents($local_path), true);
        $patterns = array_filter($patterns, function ($row) {
            //Discard comments or malformed rows
            return is_array($row) and count($row) == 3;
        });
        return $patterns;
    }
    /**
     * @dataProvider patternProvider
     */
    public function testPattern($rule, $pattern, $expected)
    {
        // Assert
        $this->assertEquals(
            $expected,
            JWadhams\JsonLogic::rule_like($rule, $pattern),
            "JsonLogic::rule_like(".json_encode($rule).", ".json_encode($pattern).") == ".json_encode($expected)
        );
    }

    /* Snappy way to test just one rule when you need to pepper in some echos into the code*/
    public function testProblematicPattern()
    {
        $raw = <<<JSON
		[
			{"*":["number", {"+":"array"}]},
			{"*" : [0.01, {"+":[{"var":"goods"}, {"var":"services"}]}]},
			true
		],


JSON;
        $raw = preg_replace('#,$#', '', trim($raw));//Drop trailing commas

        if (!json_decode($raw)) {
            throw new Exception("Couldn't parse problematic pattern");
        }
        list($pattern, $rule, $expected) = json_decode($raw, true);
        $this->assertEquals(
            $expected,
            JWadhams\JsonLogic::rule_like($rule, $pattern),
            "JsonLogic::rule_like(".json_encode($rule).", ".json_encode($pattern).") == ".json_encode($expected)
        );
    }

    public function testAddOperation()
    {
        //Set up some outside data
        $a = 0;
        // build a function operator that uses outside data by reference
        $add_to_a = function ($b=1) use (&$a) {
            $a += $b;
            return $a;
        };
        JWadhams\JsonLogic::add_operation("add_to_a", $add_to_a);
        //New operation executes, returns desired result
        //No args
        $this->assertEquals(1, JWadhams\JsonLogic::apply(["add_to_a" => []]));
        $this->assertEquals(1, $a, "Yay, side effects!");
        //Unary syntactic sugar
        $this->assertEquals(42, JWadhams\JsonLogic::apply(["add_to_a" => 41]));
        //New operation had side effects.
        $this->assertEquals(42, $a, "Yay, side effects!");

        //Calling a method with multiple var as arguments.
        JWadhams\JsonLogic::add_operation("times", function ($a, $b) {
            return $a*$b;
        });
        $this->assertEquals(
            JWadhams\JsonLogic::apply(
                ["times" => [["var"=>"a"], ["var"=>"b"]]],
                ['a'=>6,'b'=>7]
            ),
            42
        );

        //Calling a method that takes an array, but the inside of the array has rules, too
        JWadhams\JsonLogic::add_operation("array_times", function ($a) {
            return $a[0] * $a[1];
        });
        $this->assertEquals(
            JWadhams\JsonLogic::apply(
                ["array_times" => [[["var"=>"a"], ["var"=>"b"]]] ],
                ['a'=>6,'b'=>7]
            ),
            42
        );

        //Turning a language built-in function into an operation
        JWadhams\JsonLogic::add_operation("sqrt", 'sqrt');
        $this->assertEquals(42, JWadhams\JsonLogic::apply(["sqrt" => 1764]), "sqrt(1764)");

        //Turning a static method into an operation
        JWadhams\JsonLogic::add_operation("date_from_format", 'DateTime::createFromFormat');
        $this->assertEquals(
            new DateTime("1979-09-16 00:00:00"),
            JWadhams\JsonLogic::apply(["date_from_format" => ['Y-m-d h:i:s', '1979-09-16 00:00:00']]),
            "make a date"
        );
    }
}
