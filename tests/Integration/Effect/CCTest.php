<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
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
     * @return KleisliIO<IOMonad,INPUT,OUTPUT,ERR>
     */
    public function handleEffect(KleisliEffect $effect): KleisliIO
    {
        $handler = new KleisliEffectHandler();
        $runtime = BasicRuntime::create([KleisliEffect::class => $handler]);

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

        $ccEffect = KleisliEffect::id()->prompt(
            $this->abortP(KleisliEffect::id()->stubInput(5))
                ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 6))
        )->andThen(KleisliEffect::liftPure(fn ($x) => $x + 4));

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
            ->prompt(KleisliEffect::id())
            ->prompt(
                $this->abortP(KleisliEffect::id()->stubInput(5))
                    ->andThen(KleisliEffect::liftPure(fn ($x) => $x + 6))
            )->andThen(KleisliEffect::liftPure(fn ($x) => $x + 4))
        ;

        $arrow = $this->handleEffect($ccEffect);
        $result = $arrow->run(5);

        $this->assertEquals(9, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
