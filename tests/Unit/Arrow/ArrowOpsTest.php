<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Arrow;

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
}
