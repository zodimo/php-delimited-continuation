<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliArrowOps;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Arrow\Tuple;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliArrowOpsTest extends TestCase
{
    public function testFirst()
    {
        $func = fn (int $x) => IOMonad::pure($x + 10);

        $kleisliArrow = KleisliIO::arr($func);
        $arrowFirst = KleisliArrowOps::first($kleisliArrow);
        $result = $arrowFirst->run(Tuple::create(15, 'Joe'));
        $expectedResult = IOMonad::pure(Tuple::create(25, 'Joe'));
        $this->assertEquals($expectedResult, $result);
    }

    public function testSecond()
    {
        $func = fn (int $x) => IOMonad::pure($x + 10);
        $kleisliArrow = KleisliIO::arr($func);
        $arrowSecond = KleisliArrowOps::second($kleisliArrow);
        $result = $arrowSecond->run(Tuple::create('Joe', 15));
        $expectedResult = IOMonad::pure(Tuple::create('Joe', 25));
        $this->assertEquals($expectedResult, $result);
    }

    public function testCompose()
    {
        // f >>> g = g . f
        // g after f
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $kleisliArrowF = KleisliIO::arr($funcF);
        $kleisliArrowG = KleisliIO::arr($funcG);

        $arrowComposed = KleisliArrowOps::compose($kleisliArrowF, $kleisliArrowG);
        $result = $arrowComposed->run(10);
        $expectedResult = IOMonad::pure(200);
        $this->assertEquals($expectedResult, $result);
    }

    public function testMerge()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $kleisliArrowF = KleisliIO::arr($funcF);
        $kleisliArrowG = KleisliIO::arr($funcG);
        $arrowMerged = KleisliArrowOps::merge($kleisliArrowF, $kleisliArrowG);
        $result = $arrowMerged->run(Tuple::create(20, 30));
        $expectedResult = IOMonad::pure(Tuple::create(30, 300));
        $this->assertEquals($expectedResult, $result);
    }

    public function testSplit()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $kleisliArrowF = KleisliIO::arr($funcF);
        $kleisliArrowG = KleisliIO::arr($funcG);
        $arrowSplit = KleisliArrowOps::split($kleisliArrowF, $kleisliArrowG);
        $result = $arrowSplit->run(50);
        $expectedResult = IOMonad::pure(Tuple::create(60, 500));
        $this->assertEquals($expectedResult, $result);
    }
}
