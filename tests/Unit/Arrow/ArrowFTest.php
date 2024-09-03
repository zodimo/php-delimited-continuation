<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\ArrowF;

/**
 * @internal
 *
 * @coversNothing
 */
class ArrowFTest extends TestCase
{
    public function testCanCreate()
    {
        $arrow = ArrowF::arr(fn (int $x) => $x + 1);
        $this->assertInstanceOf(ArrowF::class, $arrow);
    }

    public function testCanRun()
    {
        $arrow = ArrowF::arr(fn (int $x) => $x + 1);
        $result = $arrow->run(11);
        $this->assertEquals(12, $result);
    }

    public function testId()
    {
        $arrowFId = ArrowF::id();
        $result = $arrowFId->run(100);
        $this->assertEquals(100, $result);
    }
}
