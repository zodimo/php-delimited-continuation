<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\DCF\Effect\KleisliCompositionEffect;
use Zodimo\DCF\Effect\KleisliEffect;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliCompositionEffectTest extends TestCase
{
    public function testInitializeWith()
    {
        $arrowEffect = KleisliEffect::id();
        $effect = KleisliCompositionEffect::intializeWith($arrowEffect);
        $this->assertInstanceOf(KleisliCompositionEffect::class, $effect);
        $this->assertEquals('kleisli-composition-effect.initialize-with', $effect->getTag());
        $this->assertArrayHasKey('effect', $effect->getArgs());
    }

    public function testId()
    {
        $effect = KleisliCompositionEffect::id();
        $this->assertInstanceOf(KleisliCompositionEffect::class, $effect);
        $this->assertEquals('kleisli-composition-effect.id', $effect->getTag());
        $this->assertEmpty($effect->getArgs());
    }

    public function testArr()
    {
        $f = fn ($x) => IOMonad::pure($x);
        $effect = KleisliCompositionEffect::arr($f);
        $this->assertInstanceOf(KleisliCompositionEffect::class, $effect);
        $this->assertEquals('kleisli-composition-effect.arr', $effect->getTag());
        $this->assertArrayHasKey('f', $effect->getArgs());
    }

    public function testCompose()
    {
        $effectF = KleisliCompositionEffect::id();
        $effectG = KleisliCompositionEffect::id();
        $effect = KleisliCompositionEffect::compose($effectF, $effectG);

        $this->assertEquals('kleisli-composition-effect.compose', $effect->getTag(), 'testCompose: tag');
        $this->assertNotSame($effectF, $effectG, 'testCompose: effectF!==effectG');
        $this->assertEquals($effectF, $effect->getArg('effectF'), 'testCompose: effectF');
        $this->assertEquals($effectG, $effect->getArg('effectG'), 'testCompose: effectG');
    }
}
