<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;
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
class KleisliEffectPromptControlTest extends TestCase
{
    use MockClosureTrait;

    /**
     * @template INPUT
     * @template OUTPUT
     * @template ERR
     *
     * @param KleisliEffect<INPUT,OUTPUT,ERR> $effect
     *
     * @return KleisliIO<IOMonad,INPUT,OUTPUT,ERR>
     */
    public function handle(KleisliEffect $effect): KleisliIO
    {
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

        return $handler->handle($effect, $runtime);
    }

    public function testCanRunId()
    {
        $effect = KleisliEffect::id();
        $arrow = $this->handle($effect);
        $result = $arrow->run(10);
        $expectedResult = IOMonad::pure(10);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Prompt and Control.
     */
    public function testPC1()
    {
        /**
         * extract the control Effect.
         */
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::id()
                    ->control(
                        function (callable $k) {
                            return call_user_func($k, KleisliEffect::id());
                        }
                    )
            )
            ->andThen(
                KleisliEffect::id()
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(10), $arrow->run(10));
    }

    public function testPC2()
    {
        $mockedEffect = $this->createMock(KleisliEffect::class);
        $mockedEffect->expects($this->never())->method('getArg');

        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::id()
                    ->andThen($mockedEffect)
                    ->control(
                        function (callable $_) {
                            return KleisliEffect::liftPure(fn ($x) => $x + 10);
                        }
                    )
                    ->andThen($mockedEffect)
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(10 + 10), $arrow->run(10));
    }

    public function testPC3a()
    {
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 10));
                            }
                        )
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(10 + 100 + 10), $arrow->run(10));
    }

    public function testPC3b()
    {
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 10));
                            }
                        )
                            ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 200))
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(320), $arrow->run(10));
    }

    public function testPC4()
    {
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                // all the effect will be run eveytime k is called...
                                // stub the prompt input
                                return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 10))->stubInput(10);
                            }
                        )
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(120), $arrow->run(null));
    }

    public function testPC5()
    {
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                $stubWith = function (KleisliEffect $effect, $stubbedInput) {
                                    return $effect->stubInput($stubbedInput);
                                };

                                // all the effect will be run when k is called...
                                /**
                                 * @var KleisliEffect $testEffect
                                 */
                                $testEffect = call_user_func($k, KleisliEffect::id());
                                $cond = $stubWith($testEffect, 150)->andThen(KleisliEffect::liftPure(fn ($value) => $value > 200));
                                $then = KleisliEffect::liftPure(fn ($x) => $x - 50);
                                $else = KleisliEffect::liftPure(fn ($x) => $x + 20);

                                return call_user_func($k, KleisliEffect::ifThenElse($cond, $then, $else));
                            }
                        )
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(130), $arrow->run(80), 'on True of if, 80 + 100 - 50');
    }

    public function testPC6()
    {
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                $stubWith = function (KleisliEffect $effect, $stubbedInput) {
                                    return $effect->stubInput($stubbedInput);
                                };

                                // all the effect will be run when k is called...
                                /**
                                 * @var KleisliEffect $testEffect
                                 */
                                $testEffect = call_user_func($k, KleisliEffect::id());
                                $cond = $stubWith($testEffect, 0)->andThen(KleisliEffect::liftPure(fn ($value) => $value > 200));
                                $then = KleisliEffect::liftPure(fn ($x) => $x - 50);
                                $else = KleisliEffect::liftPure(fn ($x) => $x + 20);

                                return call_user_func($k, KleisliEffect::ifThenElse($cond, $then, $else));
                            }
                        )
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(200), $arrow->run(80), 'on false of if, 80 + 100 + 20');
    }

    public function testPC7Multishot()
    {
        $effect = KleisliEffect::id()
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                $effectF = KleisliEffect::liftPure(fn ($x) => $x + 50);
                                $effectG = KleisliEffect::liftPure(fn ($x) => $x + 100);

                                return KleisliEffect::split(
                                    call_user_func($k, $effectF),
                                    call_user_func($k, $effectG),
                                );
                            }
                        )
                            ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 200))
                    )
            )
        ;

        $input = 80;
        $arrow = $this->handle($effect);
        $expectedResult = IOMonad::pure(Tuple::create(
            // 80 + 100 + [50] + 200
            430,
            // 80 + 100 + [100] + 200
            480
        ));
        $this->assertEquals($expectedResult, $arrow->run($input));
    }

    public function testPC8()
    {
        // allow multiple controls within the prompt scope

        $effect = KleisliEffect::liftPure(fn ($x) => $x + 33)
            ->prompt(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                return call_user_func(
                                    $k,
                                    KleisliEffect::liftPure(fn ($x) => $x + 10)
                                    // ->andThen(
                                    //     KleisliEffect::control(
                                    //         function (callable $k) {
                                    //             return call_user_func(
                                    //                 $k,
                                    //                 KleisliEffect::liftPure(fn ($x) => $x + 10)
                                    //             );
                                    //         }
                                    //     )
                                    // )
                                );
                            }
                        )
                    )
                    ->andThen(
                        KleisliEffect::control(
                            function (callable $k) {
                                return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 20));
                            }
                        )
                    )
                    ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 55))
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(IOMonad::pure(10 + 33 + 100 + 10 + 20 + 55), $arrow->run(10));
    }
}
