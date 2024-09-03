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
        $func = fn (int $x) => $x + 10;

        $kleisliArrow = KleisliIO::liftPure($func);
        $arrowFirst = KleisliArrowOps::first($kleisliArrow);
        $result = $arrowFirst->run(Tuple::create(15, 'Joe'));
        $expectedResult = IOMonad::pure(Tuple::create(25, 'Joe'));
        $this->assertEquals($expectedResult, $result);
    }

    public function testSecond()
    {
        $func = fn (int $x) => $x + 10;
        $kleisliArrow = KleisliIO::liftPure($func);
        $arrowSecond = KleisliArrowOps::second($kleisliArrow);
        $result = $arrowSecond->run(Tuple::create('Joe', 15));
        $expectedResult = IOMonad::pure(Tuple::create('Joe', 25));
        $this->assertEquals($expectedResult, $result);
    }

    public function testCompose()
    {
        // f >>> g = g . f
        // g after f
        $funcF = fn (int $x) => $x + 10;
        $funcG = fn (int $x) => $x * 10;

        $kleisliArrowF = KleisliIO::liftPure($funcF);
        $kleisliArrowG = KleisliIO::liftPure($funcG);

        $arrowComposed = KleisliArrowOps::compose($kleisliArrowF, $kleisliArrowG);
        $result = $arrowComposed->run(10);
        $expectedResult = IOMonad::pure(200);
        $this->assertEquals($expectedResult, $result);
    }

    public function testMerge()
    {
        $funcF = fn (int $x) => $x + 10;
        $funcG = fn (int $x) => $x * 10;

        $kleisliArrowF = KleisliIO::liftPure($funcF);
        $kleisliArrowG = KleisliIO::liftPure($funcG);
        $arrowMerged = KleisliArrowOps::merge($kleisliArrowF, $kleisliArrowG);
        $result = $arrowMerged->run(Tuple::create(20, 30));
        $expectedResult = IOMonad::pure(Tuple::create(30, 300));
        $this->assertEquals($expectedResult, $result);
    }

    public function testSplit()
    {
        $funcF = fn (int $x) => $x + 10;
        $funcG = fn (int $x) => $x * 10;

        $kleisliArrowF = KleisliIO::liftPure($funcF);
        $kleisliArrowG = KleisliIO::liftPure($funcG);
        $arrowSplit = KleisliArrowOps::split($kleisliArrowF, $kleisliArrowG);
        $result = $arrowSplit->run(50);
        $expectedResult = IOMonad::pure(Tuple::create(60, 500));
        $this->assertEquals($expectedResult, $result);
    }

    public function testIfThenElseTrue()
    {
        $funcCond = fn (int $x) => 10 == $x;
        $funcThen = fn (int $x) => $x + 2;
        $funcElse = fn (int $x) => $x - 2;

        $cond = KleisliIO::liftPure($funcCond);
        $then = KleisliIO::liftPure($funcThen);
        $else = KleisliIO::liftPure($funcElse);

        $arrow = KleisliArrowOps::ifThenElse($cond, $then, $else);
        $result = $arrow->run(10);
        $expectedResult = IOMonad::pure(12);
        $this->assertEquals($expectedResult, $result);
    }

    public function testIfThenElseFalse()
    {
        $funcCond = fn (int $x) => 10 == $x;
        $funcThen = fn (int $x) => $x + 2;
        $funcElse = fn (int $x) => $x - 2;

        $cond = KleisliIO::liftPure($funcCond);
        $then = KleisliIO::liftPure($funcThen);
        $else = KleisliIO::liftPure($funcElse);

        $arrow = KleisliArrowOps::ifThenElse($cond, $then, $else);
        $result = $arrow->run(100);
        $expectedResult = IOMonad::pure(98);

        $this->assertEquals($expectedResult, $result);
    }

    public function testWhileDo()
    {
        $funcCheck = fn (int $x) => $x < 10;
        $funcBody = fn (int $x) => $x + 2;

        $check = KleisliIO::liftPure($funcCheck);
        $body = KleisliIO::liftPure($funcBody);

        $arrow = KleisliArrowOps::whileDo($check, $body);
        $result = $arrow->run(0);
        $expectedResult = IOMonad::pure(10);
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

        $check = KleisliIO::liftPure($funcCheck);
        $body = KleisliIO::liftPure($funcBody);

        $arrow = KleisliArrowOps::whileDo($check, $body);

        // @phpstan-ignore argument.type
        $result = $arrow->run([])->unwrapSuccess(fn ($_) => []);
        $this->assertEquals(1000, count($result));
    }
}
