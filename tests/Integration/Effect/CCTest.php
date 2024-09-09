<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\Arrow\KleisliIO;
use Zodimo\BaseReturn\IOMonad;
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
class CCTest extends TestCase
{
    use MockClosureTrait;

    public function abortP(KleisliEffect $cc): KleisliEffect
    {
        // abortP :: Prompt r a -> CC r a -> CC r b
        // abortP p e = withSubCont p (\_ -> e)

        return KleisliEffect::control(fn ($_) => $cc);
    }

    /**
     * @template INPUT
     * @template OUTPUT
     * @template ERR
     *
     * @param KleisliEffect<INPUT,OUTPUT,ERR> $effect
     *
     * @return KleisliIO<INPUT,OUTPUT,ERR>
     */
    public function handleEffect(KleisliEffect $effect): KleisliIO
    {
        $handler = new KleisliEffectHandler();
        $runtime = BasicEffectRouter::create([KleisliEffect::class => $handler]);

        return $handler->handle($effect, $runtime);
    }

    public function test1()
    {
        // test1 = runCC (return 1 >>= (return . (+ 4)))
        // -- 5
        $ccEffect = KleisliEffect::liftPure(fn ($x) => $x + 4);
        $arrow = $this->handleEffect($ccEffect);
        $result = $arrow->run(1);

        $this->assertEquals(5, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function test2()
    {
        // test2 = runCC (do
        // p <- newPrompt
        // (pushPrompt p (pushPrompt p (return 5)))
        // >>= (return . (+ 4)))
        // -- 9

        $ccEffect = KleisliEffect::liftPure(fn ($x) => $x + 4);
        $arrow = $this->handleEffect($ccEffect);
        $result = $arrow->run(5);

        $this->assertEquals(9, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function test3()
    {
        // test3 = runCC (do
        // p <- newPrompt
        // (pushPrompt p (abortP p (return 5) >>= (return . (+ 6))))
        // >>= (return . (+ 4)))
        // -- 9

        $ccEffect = KleisliEffect::id()
            ->andThen(
                KleisliEffect::prompt(
                    $this->abortP(KleisliEffect::id()->stubInput(5))
                        ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 6))
                )->andThen(KleisliEffect::liftPure(fn ($x) => $x + 4))
            )
        ;

        $arrow = $this->handleEffect($ccEffect);
        $result = $arrow->run(5);

        $this->assertEquals(9, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function test4()
    {
        // test4 = runCC (do
        // p <- newPrompt
        // (pushPrompt p
        // (pushPrompt p (abortP p (return 5) >>= (return . (+ 6))))
        // >>= (return . (+ 4))))
        // -- 9

        $ccEffect = KleisliEffect::id()
            ->andThen(
                KleisliEffect::prompt(
                    KleisliEffect::id()
                )->andThen(
                    KleisliEffect::prompt(
                        $this->abortP(KleisliEffect::id()->stubInput(5))
                            ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 6))
                    )->andThen(KleisliEffect::liftPure(fn ($x) => $x + 4))
                )
            )
        ;

        $arrow = $this->handleEffect($ccEffect);
        $result = $arrow->run(5);

        $this->assertEquals(9, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testExample()
    {
        // (prompt (+ 1 (control k (k 3))))
        // The code is evaluated as follows:

        // (prompt (+ 1 (control k (k 3))))
        // => (prompt ((λ (k) (k 3)) (λ (x) (+ 1 x))))
        // => (prompt ((λ (x) (+ 1 x)) 3))
        // => (prompt (+ 1 3)) => (prompt 4) => 4

        // prompt resturns a arrow that does not take a value..
        // ie a thunk to produce the value

        $effect = KleisliEffect::prompt(
            KleisliEffect::arr(fn ($x) => IOMonad::pure(1 + $x))
                ->andThen(KleisliEffect::control(function (callable $k) {
                    return call_user_func($k, 3);
                }))
        );
        $reifiedArrow = $this->handleEffect($effect);
        $result = $reifiedArrow->run(null);
        $this->assertEquals(4, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testExample2()
    {
        // (prompt
        //   (+ 1 (control k (let ([x (k 1)] [y (k 2)])
        //                      (k (* x y))))))
        // Hence, the final result of that program is 1+2*3=7.

        $effect = KleisliEffect::prompt(
            KleisliEffect::arr(fn ($x) => IOMonad::pure(1 + $x))
                ->andThen(
                    KleisliEffect::control(
                        function (callable $k) {
                            $x = call_user_func($k, 1); // returns a thunk (effects)
                            $y = call_user_func($k, 2); // returns a thunk (effects)
                            $result = fn ($x, $y) => call_user_func($k, $x * $y);

                            return KleisliEffect::arr(fn () => IOMonad::pure(Tuple::create(null, null)))
                                // @phpstan-ignore argument.type
                                ->andThen(KleisliEffect::merge($x, $y))
                                ->flatmap(fn (Tuple $x) => $result($x->fst(), $x->snd()))
                            ;
                        }
                    )
                )
        );

        $reifiedArrow = $this->handleEffect($effect);
        $result = $reifiedArrow->run(null);
        $this->assertEquals(7, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
