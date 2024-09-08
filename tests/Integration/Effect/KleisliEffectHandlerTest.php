<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\Either;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\Tuple;
use Zodimo\DCF\Effect\BasicRuntime;
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

    public function testCanHandleFirst()
    {
        $func = fn (int $a) => IOMonad::pure($a + 10);

        $eff = KleisliEffect::arr($func);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle(KleisliEffect::first($eff), $runtime);
        $result = $arrow->run(Tuple::create(10, 'Joe'));
        $expectedResult = Tuple::create(20, 'Joe');
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleSecond()
    {
        $func = fn (int $a) => IOMonad::pure($a + 10);

        $eff = KleisliEffect::arr($func);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle(KleisliEffect::second($eff), $runtime);
        $result = $arrow->run(Tuple::create('Joe', 10));
        $expectedResult = Tuple::create('Joe', 20);
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleCompose()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $effectF = KleisliEffect::arr($funcF);
        $effectG = KleisliEffect::arr($funcG);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrowComposed = $handler->handle(KleisliEffect::compose($effectF, $effectG), $runtime);

        $result = $arrowComposed->run(10);
        $expectedResult = 200;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleMerge()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $effectF = KleisliEffect::arr($funcF);
        $effectG = KleisliEffect::arr($funcG);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrowMerged = $handler->handle(KleisliEffect::merge($effectF, $effectG), $runtime);
        $result = $arrowMerged->run(Tuple::create(20, 30));
        $expectedResult = Tuple::create(30, 300);
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleSplit()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $effectF = KleisliEffect::arr($funcF);
        $effectG = KleisliEffect::arr($funcG);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrowSplit = $handler->handle(KleisliEffect::split($effectF, $effectG), $runtime);

        $result = $arrowSplit->run(50);
        $expectedResult = Tuple::create(60, 500);
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleComposition()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $effectF = KleisliEffect::arr($funcF);
        $effectG = KleisliEffect::arr($funcG);

        $composedEffectL1 = KleisliEffect::compose($effectF, $effectG);
        $composedEffectL2 = KleisliEffect::compose($effectF, $composedEffectL1);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrowComposed = $handler->handle($composedEffectL2, $runtime);

        $result = $arrowComposed->run(10);
        $expectedResult = 300;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testTheCallStack()
    {
        // sum 0... 1000
        $funcF = function (int $x) {
            return IOMonad::pure($x + 1);
        };

        $effectAddOne = KleisliEffect::arr($funcF);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrowOneComposed = KleisliEffect::id();

        foreach (range(0, 9) as $_) {
            $arrowOneComposed = KleisliEffect::compose($arrowOneComposed, $effectAddOne);
        }
        $composedArrow = $handler->handle($arrowOneComposed, $runtime);
        $result = $composedArrow->run(0);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
        // print_r($arrowOneComposed);
        $this->assertEquals(1, 1);
    }

    public function testCanHandleLiftPure()
    {
        $func = fn (int $a) => $a + 10;

        $eff = KleisliEffect::liftPure($func);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($eff, $runtime);
        $result = $arrow->run(10);
        $expectedResult = 20;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleLiftImpure()
    {
        $func = fn (int $a) => $a + 10;

        $eff = KleisliEffect::liftImpure($func);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($eff, $runtime);
        $result = $arrow->run(10);
        $expectedResult = 20;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleBracketHappyPath()
    {
        $acquireEffect = KleisliEffect::id();
        $releaseEffect = KleisliEffect::liftPure(fn ($_) => null);
        $duringEffect = KleisliEffect::id();

        $effect = $acquireEffect->bracket($duringEffect, $releaseEffect);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);

        $result = $arrow->run(10);
        $expectedResult = Tuple::create(IOMonad::pure(10), IOMonad::pure(null));
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleFlatmap()
    {
        $arrow = KleisliEffect::liftPure(fn ($x) => $x + 5);

        $choice = function (int $x) {
            /**
             * You have the option to ignore the x in the return computation.
             */
            if ($x < 10) {
                return KleisliEffect::liftPure(fn ($y) => $y + 10);
            }

            return KleisliEffect::liftPure(fn ($y) => $y + 20);
        };

        $effect = $arrow->flatMap($choice);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);

        $this->assertEquals(12, $arrow->run(2)->unwrapSuccess($this->createClosureNotCalled()), '[2] +5 < 10  = [2] + 10');
        $this->assertEquals(27, $arrow->run(7)->unwrapSuccess($this->createClosureNotCalled()), '[7] + 5 > 10 = [7]  + 20');
    }

    public function testCanHandleStubInput()
    {
        $baseEffect = KleisliEffect::id();
        $effect = $baseEffect->stubInput(10);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);
        $this->assertEquals(10, $arrow->run(null)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleIfThenElse()
    {
        $cond = KleisliEffect::liftPure(fn (int $x) => $x > 10);
        $then = KleisliEffect::liftPure(fn (int $x) => $x + 10);
        $else = KleisliEffect::liftPure(fn (int $x) => $x + 20);
        $effect = KleisliEffect::ifThenElse($cond, $then, $else);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);
        $this->assertEquals(29, $arrow->run(9)->unwrapSuccess($this->createClosureNotCalled()), '9 < 10, 9 + 20');
        $this->assertEquals(21, $arrow->run(11)->unwrapSuccess($this->createClosureNotCalled()), '11 > 10, 11 + 10');
    }

    public function testCanHandleChoice()
    {
        $onLeft = KleisliEffect::liftPure(fn (int $x) => $x + 20);
        $onRight = KleisliEffect::liftPure(fn (int $x) => $x + 10);

        $effect = KleisliEffect::choice($onLeft, $onRight);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);
        $this->assertEquals(29, $arrow->run(Either::left(9))->unwrapSuccess($this->createClosureNotCalled()), '9 < 10, 9 + 20');
        $this->assertEquals(21, $arrow->run(Either::right(11))->unwrapSuccess($this->createClosureNotCalled()), '11 > 10, 11 + 10');
    }

    public function testCanHandlePrompt()
    {
        $innerEffect = KleisliEffect::id();
        $effect = KleisliEffect::prompt($innerEffect);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);
        $this->assertEquals(10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testCanHandleReset()
    {
        $innerEffect = KleisliEffect::id();
        $effect = KleisliEffect::reset($innerEffect);

        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle($effect, $runtime);
        $this->assertEquals(10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }
}
