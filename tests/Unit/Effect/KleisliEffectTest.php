<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Effect\KleisliEffect;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliEffectTest extends TestCase
{
    public function testArr()
    {
        $f = fn ($x) => IOMonad::pure($x);
        $effect = KleisliEffect::arr($f);
        $this->assertInstanceOf(KleisliEffect::class, $effect);
        $this->assertEquals('kleisli-effect.arr', $effect->getTag());
        $this->assertArrayHasKey('f', $effect->getArgs());
    }

    public function testId()
    {
        $effect = KleisliEffect::id();
        $this->assertInstanceOf(KleisliEffect::class, $effect);
        $this->assertEquals('kleisli-effect.id', $effect->getTag());
        $this->assertEmpty($effect->getArgs());
    }

    public function testFirst()
    {
        $idEffect = KleisliEffect::id();
        $effect = KleisliEffect::first($idEffect);
        $this->assertInstanceOf(KleisliEffect::class, $effect);
        $this->assertEquals('kleisli-effect.first', $effect->getTag());
        $this->assertEquals($idEffect, $effect->getArg('effect'));
    }

    public function testSecond()
    {
        $idEffect = KleisliEffect::id();
        $effect = KleisliEffect::second($idEffect);
        $this->assertInstanceOf(KleisliEffect::class, $effect);
        $this->assertEquals('kleisli-effect.second', $effect->getTag());
        $this->assertEquals($idEffect, $effect->getArg('effect'));
    }

    public function testCompose1()
    {
        $variant = 'variant: compose(eff, eff)';
        $f = fn ($x) => IOMonad::pure($x);
        $idEffect1 = KleisliEffect::arr($f);
        $idEffect2 = KleisliEffect::arr($f);
        $effect = KleisliEffect::compose($idEffect1, $idEffect2);
        $this->assertEquals('kleisli-effect.compose', $effect->getTag(), "{$variant}: tag");
        $this->assertNotSame($idEffect1, $idEffect2, "{$variant}:idEffect1!==idEffect2");
        $this->assertEquals($idEffect1, $effect->getArg('effectF'), "{$variant}: effectF");
        $this->assertEquals($idEffect2, $effect->getArg('effectG'), "{$variant}: effectG");
    }

    public function testCompose2()
    {
        $variant = 'variant: compose(eff, compose)';

        $idEffect1 = KleisliEffect::id();
        $idEffect2 = KleisliEffect::id();
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $effect = KleisliEffect::compose($idEffect1, $composedEffect);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect1,
                $idEffect2,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose3()
    {
        $variant = 'variant: compose(compose, eff)';

        $idEffect1 = KleisliEffect::id();
        $idEffect2 = KleisliEffect::id();
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $effect = KleisliEffect::compose($composedEffect, $idEffect1);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect2,
                $idEffect1,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose4()
    {
        $variant = 'variant: compose(compose, compose)';
        $f1 = fn (int $x) => IOMonad::pure($x + 2);
        $f2 = fn (int $x) => IOMonad::pure($x * 3);

        $idEffect1 = KleisliEffect::arr($f1);
        $idEffect2 = KleisliEffect::arr($f2);
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $effect = KleisliEffect::compose($composedEffect, $composedEffect);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect2,
                $idEffect1,
                $idEffect2,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose5()
    {
        $variant = 'variant: compose(eff, composition)';
        $f1 = fn (int $x) => IOMonad::pure($x + 2);
        $f2 = fn (int $x) => IOMonad::pure($x * 3);

        $idEffect1 = KleisliEffect::arr($f1);
        $idEffect2 = KleisliEffect::arr($f2);
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $compositionEff = KleisliEffect::compose($composedEffect, $composedEffect);
        $effect = KleisliEffect::compose($idEffect1, $compositionEff);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect1,
                $idEffect2,
                $idEffect1,
                $idEffect2,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose6()
    {
        $variant = 'variant: compose(composition, eff)';
        $f1 = fn (int $x) => IOMonad::pure($x + 2);
        $f2 = fn (int $x) => IOMonad::pure($x * 3);

        $idEffect1 = KleisliEffect::arr($f1);
        $idEffect2 = KleisliEffect::arr($f2);
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $compositionEff = KleisliEffect::compose($composedEffect, $composedEffect);
        $effect = KleisliEffect::compose($compositionEff, $idEffect1);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect2,
                $idEffect1,
                $idEffect2,
                $idEffect1,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose7()
    {
        $variant = 'variant: compose(composition, composition)';
        $f1 = fn (int $x) => IOMonad::pure($x + 2);
        $f2 = fn (int $x) => IOMonad::pure($x * 3);

        $idEffect1 = KleisliEffect::arr($f1);
        $idEffect2 = KleisliEffect::arr($f2);
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $compositionEff = KleisliEffect::compose($composedEffect, $idEffect2);
        $effect = KleisliEffect::compose($compositionEff, $compositionEff);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect2,
                $idEffect2,
                $idEffect1,
                $idEffect2,
                $idEffect2,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose8()
    {
        $variant = 'variant: compose(compose, composition)';
        $f1 = fn (int $x) => IOMonad::pure($x + 2);
        $f2 = fn (int $x) => IOMonad::pure($x * 3);

        $idEffect1 = KleisliEffect::arr($f1);
        $idEffect2 = KleisliEffect::arr($f2);
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $compositionEff = KleisliEffect::compose($composedEffect, $idEffect2);
        $effect = KleisliEffect::compose($composedEffect, $compositionEff);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect2,
                $idEffect1,
                $idEffect2,
                $idEffect2,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }

    public function testCompose9()
    {
        $variant = 'variant: compose(composition, compose)';
        $f1 = fn (int $x) => IOMonad::pure($x + 2);
        $f2 = fn (int $x) => IOMonad::pure($x * 3);

        $idEffect1 = KleisliEffect::arr($f1);
        $idEffect2 = KleisliEffect::arr($f2);
        $composedEffect = KleisliEffect::compose($idEffect1, $idEffect2);
        $compositionEff = KleisliEffect::compose($composedEffect, $idEffect2);
        $effect = KleisliEffect::compose($compositionEff, $composedEffect);
        $this->assertEquals('kleisli-effect.composition', $effect->getTag(), "{$variant}: tag");
        $expectedArgs = [
            'effects' => [
                $idEffect1,
                $idEffect2,
                $idEffect2,
                $idEffect1,
                $idEffect2,
            ],
        ];
        $this->assertSame($expectedArgs, $effect->getArgs(), "{$variant}: effects");
    }
}
