<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\Tuple;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliEffectHandlerTest extends TestCase
{
    public function testCanHandleFirst()
    {
        $func = fn (int $a) => IOMonad::pure($a + 10);

        $eff = KleisliEffect::arr($func);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle(KleisliEffect::first($eff), $runtime);
        $result = $arrow->run(Tuple::create(10, 'Joe'));
        $expectedResult = IOMonad::pure(Tuple::create(20, 'Joe'));
        $this->assertEquals($expectedResult, $result);
    }

    public function testCanHandleSecond()
    {
        $func = fn (int $a) => IOMonad::pure($a + 10);

        $eff = KleisliEffect::arr($func);
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        $arrow = $handler->handle(KleisliEffect::second($eff), $runtime);
        $result = $arrow->run(Tuple::create('Joe', 10));
        $expectedResult = IOMonad::pure(Tuple::create('Joe', 20));
        $this->assertEquals($expectedResult, $result);
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
        $expectedResult = IOMonad::pure(200);
        $this->assertEquals($expectedResult, $result);
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
        $expectedResult = IOMonad::pure(Tuple::create(30, 300));
        $this->assertEquals($expectedResult, $result);
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
        $expectedResult = IOMonad::pure(Tuple::create(60, 500));
        $this->assertEquals($expectedResult, $result);
    }
}
