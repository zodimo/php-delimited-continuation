<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Effect\KleisliCompositionEffect;
use Zodimo\DCF\Effect\KleisliCompositionEffectHandler;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\Runtime;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliCompositionEffectHandlerTest extends TestCase
{
    use MockClosureTrait;

    public function testCanCreate()
    {
        $handler = new KleisliCompositionEffectHandler();
        $this->assertInstanceOf(KleisliCompositionEffectHandler::class, $handler);
    }

    public function testCanHandleInitializeWith()
    {
        $arrowEffect = KleisliEffect::id();
        $compositionEffect = KleisliCompositionEffect::intializeWith($arrowEffect);
        $handler = new KleisliCompositionEffectHandler();

        $runtime = $this->createMock(Runtime::class);
        $runtime->expects($this->once())->method('perform')->with($arrowEffect)->willReturn(KleisliIO::id());

        $arrow = $handler->handle($compositionEffect, $runtime);
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleId()
    {
        $compositionEffect = KleisliCompositionEffect::id();
        $handler = new KleisliCompositionEffectHandler();

        $runtime = $this->createMock(Runtime::class);
        $runtime->expects($this->never())->method('perform');

        $arrow = $handler->handle($compositionEffect, $runtime);
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandlerArr()
    {
        $func = fn (int $a) => IOMonad::pure($a);
        $runtime = $this->createMock(Runtime::class);
        $handler = new KleisliCompositionEffectHandler();

        $arrow = $handler->handle(KleisliCompositionEffect::arr($func), $runtime);
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
