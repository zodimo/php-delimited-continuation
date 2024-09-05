<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\ArrowF;
use Zodimo\DCF\Arrow\ArrowOps;
use Zodimo\DCF\Arrow\Tuple;

/**
 * @internal
 *
 * @coversNothing
 */
class ArrowOpsTest extends TestCase
{
    public function testFirst()
    {
        $arrowF = ArrowF::arr(fn (int $x) => $x + 10);
        $arrowFirst = ArrowOps::first($arrowF);
        $result = $arrowFirst->run(Tuple::create(15, 'Joe'));
        $expectedResult = Tuple::create(25, 'Joe');
        $this->assertEquals($expectedResult, $result);
    }

    public function testSecond()
    {
        $arrowF = ArrowF::arr(fn (int $x) => $x + 10);
        $arrowSecond = ArrowOps::second($arrowF);
        $result = $arrowSecond->run(Tuple::create('Joe', 15));
        $expectedResult = Tuple::create('Joe', 25);
        $this->assertEquals($expectedResult, $result);
    }

    public function testCompose()
    {
        $arrowFF = ArrowF::arr(fn (int $x) => $x + 10);
        $arrowFG = ArrowF::arr(fn (int $x) => $x * 10);
        $arrowComposed = ArrowOps::compose($arrowFF, $arrowFG);
        $result = $arrowComposed->run(10);
        $expectedResult = 200;
        $this->assertEquals($expectedResult, $result);
    }

    public function testMerge()
    {
        $arrowFF = ArrowF::arr(fn (int $x) => $x + 10);
        $arrowFG = ArrowF::arr(fn (int $x) => $x * 10);
        $arrowMerged = ArrowOps::merge($arrowFF, $arrowFG);
        $result = $arrowMerged->run(Tuple::create(20, 30));
        $expectedResult = Tuple::create(30, 300);
        $this->assertEquals($expectedResult, $result);
    }

    public function testSplit()
    {
        $arrowFF = ArrowF::arr(fn (int $x) => $x + 10);
        $arrowFG = ArrowF::arr(fn (int $x) => $x * 10);
        $arrowSplit = ArrowOps::split($arrowFF, $arrowFG);
        $result = $arrowSplit->run(50);
        $expectedResult = Tuple::create(60, 500);
        $this->assertEquals($expectedResult, $result);
    }

    public function testIfThenElseTrue()
    {
        $cond = ArrowF::arr(fn (int $x) => 10 == $x);
        $then = ArrowF::arr(fn (int $x) => $x + 2);
        $else = ArrowF::arr(fn (int $x) => $x - 2);

        $arrow = ArrowOps::ifThenElse($cond, $then, $else);
        $result = $arrow->run(10);
        $expectedResult = 12;
        $this->assertEquals($expectedResult, $result);
    }

    public function testIfThenElseFalse()
    {
        $cond = ArrowF::arr(fn (int $x) => 10 == $x);
        $then = ArrowF::arr(fn (int $x) => $x + 2);
        $else = ArrowF::arr(fn (int $x) => $x - 2);

        $arrow = ArrowOps::ifThenElse($cond, $then, $else);
        $result = $arrow->run(100);
        $expectedResult = 98;
        $this->assertEquals($expectedResult, $result);
    }

    public function testWhileDo()
    {
        $check = ArrowF::arr(fn (int $x) => $x < 10);
        $body = ArrowF::arr(fn (int $x) => $x + 2);

        $arrow = ArrowOps::whileDo($check, $body);
        $result = $arrow->run(0);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result);
    }

    public function testArrayFill1000()
    {
        $funcCheck = fn (array $x) => count($x) < 1000;
        $funcBody = function (array $x) {
            $size = count($x);
            $x[] = $size;

            return $x;
        };

        $check = ArrowF::arr($funcCheck);
        $body = ArrowF::arr($funcBody);

        $arrow = ArrowOps::whileDo($check, $body);

        $result = $arrow->run([]);
        $this->assertEquals(1000, count($result));
    }
}
