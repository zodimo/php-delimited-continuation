<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\Arrow\KleisliIO;
use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliEffectResetShiftTest extends TestCase
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
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

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
     * Reset and Shift.
     */
    public function testRS1()
    {
        /**
         * extract the control Effect.
         */
        $effect = KleisliEffect::id()
            ->reset(
                KleisliEffect::id()
                    ->shift(
                        function (callable $k) {
                            return call_user_func($k, KleisliEffect::id());
                        }
                    )
                    ->andThen(KleisliEffect::id())
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testRS2()
    {
        $mockedEffect = $this->createMock(KleisliEffect::class);
        $mockedEffect->expects($this->never())->method('getArg');

        $effect = KleisliEffect::id()
            ->reset(
                KleisliEffect::id()
                    ->andThen($mockedEffect)
                    ->shift(
                        function (callable $_) {
                            return KleisliEffect::liftPure(fn ($x) => $x + 10);
                        }
                    )
                    ->andThen($mockedEffect)
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(20, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testRS3a()
    {
        $effect = KleisliEffect::id()
            ->reset(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::shift(
                            function (callable $k) {
                                return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 10));
                            }
                        )
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(10 + 100 + 10, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testRS3b()
    {
        $mockClosure = $this->createClosureMock();
        $mockClosure->expects($this->never())->method('__invoke');
        // liftPure to let phpunit exceptions through
        $afterShiftEffect = KleisliEffect::liftPure($mockClosure);

        $effect = KleisliEffect::id()
            ->reset(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::shift(
                            function (callable $k) {
                                return call_user_func($k, KleisliEffect::liftPure(fn ($x) => $x + 10));
                            }
                        )
                            ->andThen($afterShiftEffect)
                    )
                    ->andThen($afterShiftEffect)
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(120, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testRS7Multishot()
    {
        $effect = KleisliEffect::id()
            ->reset(
                KleisliEffect::liftPure(fn ($x) => $x + 100)
                    ->andThen(
                        KleisliEffect::shift(
                            function (callable $k) {
                                $effectF = KleisliEffect::liftPure(fn ($x) => $x + 50);
                                $effectG = KleisliEffect::liftPure(fn ($x) => $x + 100);

                                return KleisliEffect::split(
                                    call_user_func($k, $effectF),
                                    call_user_func($k, $effectG),
                                );
                            }
                        )->andThen(KleisliEffect::liftPure(fn ($x) => $x + 200)) // ignore by shift
                    )
                    ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 200)) // ignore by shift
            )
        ;

        $input = 80;
        $arrow = $this->handle($effect);
        $expectedResult = Tuple::create(
            // 80 + 100 + [50]
            230,
            // 80 + 100 + [100]
            280
        );
        $this->assertEquals($expectedResult, $arrow->run($input)->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testRS8()
    {
        $effect = KleisliEffect::id()
            ->reset(
                KleisliEffect::liftPure(fn ($a) => $a + 100)
                    ->andThen(
                        KleisliEffect::shift(
                            function (callable $k) {
                                return call_user_func(
                                    $k,
                                    KleisliEffect::liftPure(fn ($b) => $b + 10)
                                        ->andThen(
                                            KleisliEffect::shift(
                                                function (callable $k) {
                                                    return call_user_func($k, KleisliEffect::liftPure(fn ($c) => $c + 20));
                                                }
                                            )
                                        )
                                );
                            }
                        )
                    )
            )
        ;

        $arrow = $this->handle($effect);
        $this->assertEquals(10 + 100 + 10 + 20, $arrow->run(10)->unwrapSuccess($this->createClosureNotCalled()));
    }
}
