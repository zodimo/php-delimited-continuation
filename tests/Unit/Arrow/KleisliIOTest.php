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

        // is an arrow in io...
        $arrow = KleisliIO::arr($func);
        $this->assertInstanceOf(KleisliIO::class, $arrow);
    }

    public function testCanCreateWithId()
    {
        // is an arrow in io...
        $arrow = KleisliIO::id();
        $this->assertInstanceOf(KleisliIO::class, $arrow);
    }

    public function testCanCallFromArr()
    {
        /**
         * @var callable(int):IOMonad<int, never> $func
         */
        $func = fn (int $a) => IOMonad::pure($a);

        // is an arrow in io...
        $arrow = KleisliIO::arr($func);
        $result = $arrow->run(10);

        $expectedResult = IOMonad::pure(10);

        $this->assertEquals($expectedResult, $result);
    }

    public function testCanCallFromId()
    {
        // is an arrow in io...
        $arrow = KleisliIO::id();
        $result = $arrow->run(10);

        $expectedResult = IOMonad::pure(10);

        $this->assertEquals($expectedResult, $result);
    }
}
