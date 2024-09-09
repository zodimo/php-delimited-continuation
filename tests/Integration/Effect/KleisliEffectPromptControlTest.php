<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\Arrow\KleisliIO;
use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Effect\Router\BasicEffectRouter;
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
     * @return KleisliIO<INPUT,OUTPUT,ERR>
     */
    public function handle(KleisliEffect $effect): KleisliIO
    {
        $handler = new KleisliEffectHandler();
        $runtime = BasicEffectRouter::create([KleisliEffect::class => $handler]);

        return $handler->handle($effect, $runtime);
    }

    public function testCanRunId()
    {
        $effect = KleisliEffect::id();
        $arrow = $this->handle($effect);
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
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
            ->andThen(
                KleisliEffect::prompt(
                    KleisliEffect::id()
                        ->control(
                            function (callable $k) {
                                return call_user_func($k, KleisliEffect::id());
                            }
                        )
                )
            )
            ->andThen(
                KleisliEffect::id()
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testPC2()
    {
        $mockedEffect = $this->createMock(KleisliEffect::class);
        $mockedEffect->expects($this->never())->method('getArg');

        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100)
            ->andThen(
                KleisliEffect::prompt(
                    KleisliEffect::id()
                        ->andThen($mockedEffect)
                        ->control(
                            function (callable $_) {
                                return KleisliEffect::liftPure(fn ($x) => $x + 10);
                            }
                        )
                        ->andThen($mockedEffect)
                )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(100 + 10 + 10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testPC3a()
    {
        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100)
            ->andThen(
                KleisliEffect::prompt(
                    KleisliEffect::liftPure(fn ($x) => $x + 100)
                        ->andThen(
                            KleisliEffect::control(
                                function (callable $k) {
                                    return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 10));
                                }
                            )
                        )
                )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(10 + 100 + 100 + 10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testPC3b()
    {
        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100)
            ->andThen(
                KleisliEffect::prompt(
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
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(100 + 100 + 10 + 200 + 10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testPC4()
    {
        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100) // not pass on because input stubbed in control
            ->andThen(
                KleisliEffect::prompt(
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
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(100 + 10 + 10, $arrow->run(null)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testPC5()
    {
        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100)
            ->andThen(
                KleisliEffect::prompt(
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
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(230, $arrow->run(80)->unwrapSuccess($this->createClosureNotCalled()), 'on True of if, 100 + 80 + 100 - 50');
    }

    public function testPC6()
    {
        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100)
            ->andThen(
                KleisliEffect::prompt(
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
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(300, $arrow->run(80)->unwrapSuccess($this->createClosureNotCalled()), 'on false of if, 100 + 80 + 100 + 20');
    }

    public function testPC7Multishot()
    {
        $effect = KleisliEffect::liftPure(fn ($x) => $x + 100)
            ->andThen(
                KleisliEffect::prompt(
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
            )
        ;

        $input = 80;
        $arrow = $this->handle($effect);
        $expectedResult = Tuple::create(
            // 100 + 80 + 100 + [50] + 200
            530,
            // 100 + 80 + 100 + [100] + 200
            580
        );
        $this->assertEquals($expectedResult, $arrow->run($input)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testPC8()
    {
        // allow multiple controls within the prompt scope

        $effect = KleisliEffect::liftPure(fn ($x) => $x + 33)
            ->andThen(
                KleisliEffect::prompt(
                    KleisliEffect::liftPure(fn ($x) => $x + 100)
                        ->andThen(
                            KleisliEffect::control(
                                function (callable $k) {
                                    return call_user_func(
                                        $k,
                                        KleisliEffect::liftPure(fn ($x) => $x + 10)
                                            ->andThen(
                                                KleisliEffect::control(
                                                    function (callable $k) {
                                                        return call_user_func(
                                                            $k,
                                                            KleisliEffect::liftPure(fn ($x) => $x + 10)
                                                        );
                                                    }
                                                )
                                            )
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
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(10 + 33 + 100 + 10 + 20 + 10 + 55, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }
}
