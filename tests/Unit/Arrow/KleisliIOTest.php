<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliIOTest extends TestCase
{
    public function testCanCreateWithArr()
    {
        $func = fn ($a) => IOMonad::pure($a);

        $arrow = KleisliIO::arr($func);
        $this->assertInstanceOf(KleisliIO::class, $arrow);
    }

    public function testCanCreateWithId()
    {
        $arrow = KleisliIO::id();
        $this->assertInstanceOf(KleisliIO::class, $arrow);
    }

    public function testCanCallFromArr()
    {
        /**
         * @var callable(int):IOMonad<int, never> $func
         */
        $func = fn (int $a) => IOMonad::pure($a);

        $arrow = KleisliIO::arr($func);
        $result = $arrow->run(10);

        $expectedResult = IOMonad::pure(10);

        $this->assertEquals($expectedResult, $result);
    }

    public function testCanCallFromId()
    {
        $arrow = KleisliIO::id();
        $result = $arrow->run(10);

        $expectedResult = IOMonad::pure(10);

        $this->assertEquals($expectedResult, $result);
    }

    public function testImpureWithSuccess()
    {
        $arrow = KleisliIO::impure(fn ($x) => $x + 1);
        $result = $arrow->run(10);

        $expectedResult = IOMonad::pure(11);

        $this->assertEquals($expectedResult, $result);
    }

    public function testImpureWithFailure()
    {
        $exception = new \RuntimeException('oops');
        $arrow = KleisliIO::impure(function ($_) use ($exception) { throw $exception; });
        $result = $arrow->run(10);

        $expectedResult = IOMonad::fail($exception)->unwrapFailure(fn ($_) => new \RuntimeException('not this... '));

        $this->assertEquals($expectedResult, $result->unwrapFailure(fn ($_) => new \RuntimeException('also not this..')));
    }
}
