<?php

use PHPUnit\Framework\TestCase;

/**
 * `==` / `!=` must match JavaScript's abstract equality for `null`, so a rule
 * evaluates identically in json-logic-js (front end) and this library (back end).
 *
 * In JS, `null` is loosely-equal only to `null`/`undefined` — never to 0, false
 * or "". PHP's native `==` coerces `null` to those, so `null == 0` wrongly
 * returned true here while json-logic-js returns false.
 */
class NullLooseEqualityTest extends TestCase
{
    /**
     * @dataProvider nullEqualityProvider
     */
    public function testNullEquality($logic, $expected)
    {
        $this->assertSame(
            $expected,
            JWadhams\JsonLogic::apply($logic),
            "JsonLogic::apply(" . json_encode($logic) . ") === " . json_encode($expected)
        );
    }

    public function nullEqualityProvider()
    {
        return [
            // null is loosely-equal only to null (JS: null == undefined too, but
            // JSON has no undefined — a missing var resolves to null).
            'null == null'      => [['==' => [null, null]], true],
            'null == 0'         => [['==' => [null, 0]], false],
            'null == 0.0'       => [['==' => [null, 0.0]], false],
            'null == false'     => [['==' => [null, false]], false],
            'null == ""'        => [['==' => [null, '']], false],
            'null == "0"'       => [['==' => [null, '0']], false],
            'null == []'        => [['==' => [null, []]], false],
            '0 == null'         => [['==' => [0, null]], false],
            'false == null'     => [['==' => [false, null]], false],

            // != is the negation.
            'null != null'      => [['!=' => [null, null]], false],
            'null != 0'         => [['!=' => [null, 0]], true],
            'null != false'     => [['!=' => [null, false]], true],
            '0 != null'         => [['!=' => [0, null]], true],

            // Non-null coercion is unchanged (matches JS already).
            '"0" == 0'          => [['==' => ['0', 0]], true],
            '1 == 1'            => [['==' => [1, 1]], true],
            '0 == 0'            => [['==' => [0, 0]], true],
            'false == 0'        => [['==' => [false, 0]], true],
            '1 != 2'            => [['!=' => [1, 2]], true],
        ];
    }
}
