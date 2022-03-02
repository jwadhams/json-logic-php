<?php

use PHPUnit\Framework\TestCase;

class LazyEvaluationTest extends TestCase
{
    protected $mutates = 0;

    public function setUp(): void
    {
        JWadhams\JsonLogic::add_operation('up', function () { return ++$this->mutates; });
        JWadhams\JsonLogic::add_operation('down', function () { return --$this->mutates; });
    }

    public function testLazyIf()
    {
        $this->mutates = 0;
        JWadhams\JsonLogic::apply(['if'=> [
            true,
            ['up' => []],
            ['down' => []]
        ]]);
        self::assertSame(1, $this->mutates, "Mutates should increment and not decrement");

        $this->mutates = 0;
        JWadhams\JsonLogic::apply(['if'=> [
            false,
            ['up' => []],
            ['down' => []]
        ]]);
        self::assertSame(-1, $this->mutates, "Mutates should decrement and not increment");

    }

    public function testAndEvaluatesEveryTruthyArgument()
    {
        $this->mutates = 0;
        $returnValue = JWadhams\JsonLogic::apply(['and'=> [
            ['up' => []],
            ['up' => []],
            ['up' => []]
        ]]);
        self::assertSame(3, $returnValue);
        self::assertSame(3, $this->mutates, "All mutates return truthy, all run");
    }

    public function testAndHaltsOnFirstFalsyArgument(): void
    {
        $this->mutates = 0;
        $returnValue = JWadhams\JsonLogic::apply(['and'=> [
            false,
            ['up' => []],
            ['up' => []]
        ]]);
        self::assertSame(false, $returnValue);
        self::assertSame(0, $this->mutates, "Mutates should never run");

        $this->mutates = 0;
        $returnValue = JWadhams\JsonLogic::apply(['and'=> [
            ['up' => []],
            false,
            ['up' => []]
        ]]);
        self::assertSame(false, $returnValue);
        self::assertSame(1, $this->mutates, "First 'up' should run, halt on 'false' don't evaluate second 'up'");
    }

    public function testOrEvaluatesEveryFalsyArgument()
    {
        $this->mutates = 1;
        $returnValue = JWadhams\JsonLogic::apply(['or'=> [
            false,
            false,
            ['down' => []]
        ]]);
        self::assertSame(0, $returnValue);
        self::assertSame(0, $this->mutates, "All mutates return falsy, all run");
    }

    public function testOrHaltsOnFirstTruthyArgument(): void
    {
        $this->mutates = 0;
        $returnValue = JWadhams\JsonLogic::apply(['or'=> [
            true,
            ['down' => []],
            ['down' => []]
        ]]);
        self::assertSame(true, $returnValue);
        self::assertSame(0, $this->mutates, "Neither 'down' should run");

        $this->mutates = 1;
        $returnValue = JWadhams\JsonLogic::apply(['or'=> [
            ['down' => []],
            ['down' => []],
            ['down' => []]
        ]]);
        self::assertSame(-1, $returnValue);
        self::assertSame(-1, $this->mutates, "First 'down' should run, second 'down' runs and returns -1 which is truthy, third 'down' does not run");
    }

}
