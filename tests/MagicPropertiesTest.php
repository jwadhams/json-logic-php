<?php
/**
 * Test that JsonLogic makes smart decisions on PHP objects that use "magic" properties
 */
 declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use JWadhams\JsonLogic;

class MagicPropertiesTest extends TestCase
{
    private function getMagicalObject()
    {
        return new class {
            public function __get($key)
            {
                return "magic";
            }
            public function __isset($key)
            {
                return $key === "defined";
            }
            public function method()
            {
                return "I am not a property!";
            }
        };
    }

    public function testValidMagicWorks()
    {
        $object = $this->getMagicalObject();
        $rule = ['var'=>'object.defined'];
        $data = ['object' => $object];
        $this->assertEquals('magic', JsonLogic::apply($rule, $data));
    }

    public function testMixtureOfRealPropertiesAndMagicWorks()
    {
        $object = $this->getMagicalObject();
        $rule = ['var'=>'object.defined'];
        $data = new stdClass();
        $data->object = $object;
        $this->assertEquals('magic', JsonLogic::apply($rule, $data));
    }

    public function testInvalidMagicReturnsDefault()
    {
        $object = $this->getMagicalObject();
        $rule = ['var'=>['object.undefined', 'default']];
        $data = ['object' => $object];
        $this->assertEquals('default', JsonLogic::apply($rule, $data));
    }

    public function testMethodsShouldReturnDefault()
    {
        $object = $this->getMagicalObject();
        $rule = ['var'=>['object.method', 'default']];
        $data = ['object' => $object];
        $this->assertEquals('default', JsonLogic::apply($rule, $data));
    }
}
