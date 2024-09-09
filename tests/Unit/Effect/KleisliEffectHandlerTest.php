<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\DCF\Effect\EffectRouter;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliEffectHandlerTest extends TestCase
{
    use MockClosureTrait;

    public function testCanCreate()
    {
        $handler = new KleisliEffectHandler();
        $this->assertInstanceOf(KleisliEffectHandler::class, $handler);
    }

    public function testCanHandlerId()
    {
        $runtime = $this->createMock(EffectRouter::class);
        $handler = new KleisliEffectHandler();

        $arrow = $handler->handle(KleisliEffect::id(), $runtime);
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandlerArr()
    {
        $func = fn (int $a) => IOMonad::pure($a);
        $runtime = $this->createMock(EffectRouter::class);
        $handler = new KleisliEffectHandler();

        $arrow = $handler->handle(KleisliEffect::arr($func), $runtime);
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
