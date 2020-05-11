<?php
/**
 * Test that JsonLogic makes smart decisions on PHP objects that use array accessors.
 * e.g. Laravel Collections
 */
 declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use JWadhams\JsonLogic;

class ObjectWithArrayAccessorsTest extends TestCase
{
    private function getArrayAccessibleObject()
    {
        return new class implements ArrayAccess{
            public $property = 'object-ish';

            public function offsetExists ($offset)
            {
                return $offset === 'defined';
            }
            public function offsetGet ($offset)
            {
                return 'array-ish';
            }
            public function offsetSet ($offset, $value)
            {
                throw new Exception("Required by ArrayAccess interface, unusable by JsonLogic");
            }
            public function offsetUnset ($offset)
            {
                throw new Exception("Required by ArrayAccess interface, unusable by JsonLogic");
            }
        };
    }

    public function testValidArrayAccessWorks()
    {
        $object = $this->getArrayAccessibleObject();
        $rule = ['var'=>['defined', 'default']];
        $this->assertEquals('array-ish', $object['defined']);
        $this->assertEquals('array-ish', JsonLogic::apply($rule, $object));
    }

    public function testInvalidArrayAccessReturnsDefault()
    {
        $object = $this->getArrayAccessibleObject();
        $rule = ['var'=>['undefined', 'default']];
        $this->assertFalse(isset($object['undefined']));
        $this->assertEquals('default', JsonLogic::apply($rule, $object));
    }

    public function testCanMixPropertiesAndArrayAccess()
    {
        $object = $this->getArrayAccessibleObject();
        $rule = ['var'=>['property', 'default']];
        $this->assertFalse(isset($object['property']));
        $this->assertEquals('object-ish', $object->property);
        $this->assertEquals('object-ish', JsonLogic::apply($rule, $object));
    }
}
