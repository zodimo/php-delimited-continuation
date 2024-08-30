<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Frame;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Frame\CCMonad;
use Zodimo\DCF\Frame\Frame;
use Zodimo\DCF\Prompt\Prompt;

/**
 * @internal
 *
 * @coversNothing
 */
class CCMonadTest extends TestCase
{
    public function abortP(Prompt $p, CCMonad $cc): CCMonad
    {
        // abortP :: Prompt r a -> CC r a -> CC r b
        // abortP p e = withSubCont p (\_ -> e)

        return CCMonad::withSubCont($p, fn ($_) => $cc);
    }

    /**
     * @param callable(Prompt): CCMonad $f
     */
    public function promptP(callable $f): CCMonad
    {
        // promptP :: (Prompt r a -> CC r a) -> CC r a
        // promptP f = do p <- newPrompt; pushPrompt p (f p)
        return CCMonad::newPrompt()
            ->flatmap(fn ($p) => CCMonad::pushPrompt($p, call_user_func($f, $p)))
        ;
    }

    /**
     * @template R
     * @template A
     * @template B
     *
     * @param Prompt<R, B>                                                  $p
     * @param callable(callable(CCMonad<R, A>):CCMonad<R, B>):CCMonad<R, B> $f
     *
     * @return CCMonad<R, A>
     */
    public function controlP(Prompt $p, callable $f): CCMonad
    {
        // controlP :: Prompt r b -> ((CC r a -> CC r b) -> CC r b) -> CC r a
        // controlP p f = withSubCont p $ \sk ->
        //                pushPrompt p (f (\c -> pushSubCont sk c))
        return CCMonad::withSubCont(
            $p,
            fn ($subSeq) => CCMonad::pushPrompt(
                $p,
                call_user_func(
                    $f,
                    fn ($c) => CCMonad::pushSubCont($subSeq, $c)
                )
            )
        );
    }

    /**
     * @template R
     * @template A
     * @template B
     *
     * @param Prompt<R, B>                                                  $p
     * @param callable(callable(CCMonad<R, A>):CCMonad<R, B>):CCMonad<R, B> $f
     *
     * @return CCMonad<R, A>
     */
    public function shiftP(Prompt $p, callable $f): CCMonad
    {
        // shiftP :: Prompt r b -> ((CC r a -> CC r b) -> CC r b) -> CC r a
        // shiftP p f = withSubCont p $ \sk ->
        //                pushPrompt p (f (\c ->
        //                  pushPrompt p (pushSubCont sk c)))
        return CCMonad::withSubCont(
            $p,
            fn ($subSeq) => CCMonad::pushPrompt(
                $p,
                call_user_func($f, fn (CCMonad $c) => CCMonad::pushPrompt(
                    $p,
                    CCMonad::pushSubCont($subSeq, $c)
                ))
            )
        );
    }

    public function testReturnFunction()
    {
        $cc = CCMonad::create(fn ($x) => $x + 10)->flatmap(fn ($fun) => CCMonad::create(call_user_func($fun, 4)));
        $result = CCMonad::runCC($cc);
        $this->assertEquals(14, $result);
    }

    public function test1()
    {
        // test1 = runCC (return 1 >>= (return . (+ 4)))
        // -- 5

        $cc = CCMonad::create(1)->flatmap(fn ($x) => CCMonad::create($x + 4));
        $result = CCMonad::runCC($cc);
        $this->assertEquals(5, $result);
    }

    public function test2()
    {
        // test2 = runCC (do
        // p <- newPrompt
        // (pushPrompt p (pushPrompt p (return 5)))
        // >>= (return . (+ 4)))
        // -- 9

        $cc = CCMonad::newPrompt()
            ->flatmap(fn ($p) => CCMonad::pushPrompt($p, CCMonad::pushPrompt($p, CCMonad::create(5))))
            ->flatmap(fn ($x) => CCMonad::create($x + 4))
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(9, $result);
    }

    public function test3()
    {
        // test3 = runCC (do
        // p <- newPrompt
        // (pushPrompt p (abortP p (return 5) >>= (return . (+ 6))))
        // >>= (return . (+ 4)))
        // -- 9

        $cc = CCMonad::newPrompt()
            ->flatmap(fn ($p) => CCMonad::pushPrompt($p, $this->abortP($p, CCMonad::create(5))
                ->flatmap(fn ($x) => CCMonad::create($x + 6))))
            ->flatmap(fn ($x) => CCMonad::create($x + 4))
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(9, $result);
    }

    public function test4()
    {
        // test4 = runCC (do
        // p <- newPrompt
        // (pushPrompt p
        // (pushPrompt p (abortP p (return 5) >>= (return . (+ 6))))
        // >>= (return . (+ 4))))
        // -- 9

        $cc = CCMonad::newPrompt()
            ->flatmap(fn ($p) => CCMonad::pushPrompt(
                $p,
                CCMonad::pushPrompt(
                    $p,
                    $this->abortP($p, CCMonad::create(5))
                        ->flatmap(fn ($x) => CCMonad::create($x + 6))
                )
                    ->flatmap(fn ($x) => CCMonad::create($x + 4))
            ))
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(9, $result);
    }

    public function test5()
    {
        // test5 = runCC (do
        //     p <- newPrompt
        //     v <- pushPrompt p $
        //        do
        //          v1 <- pushPrompt p (abortP p (return 5) >>= (return . (+ 6)))
        //          v1 <- abortP p (return 7)
        //          return $ v1 + 10
        //          return $ v + 20
        //     )
        // -- 27

        $cc = CCMonad::newPrompt()
            ->flatmap(
                fn ($p) => CCMonad::pushPrompt(
                    $p,
                    CCMonad::pushPrompt(
                        $p,
                        $this->abortP($p, CCMonad::create(5)->flatmap(fn ($x) => CCMonad::create($x + 6)))
                    )
                        ->flatmap(
                            fn ($v1) => $this->abortP($p, CCMonad::create(7))
                                ->flatmap(fn ($v1) => CCMonad::create($v1 + 10))
                        )
                )
                    ->flatmap(fn ($v) => CCMonad::create($v + 20))
            )
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(27, $result);
    }

    public function test6()
    {
        // -- From Zena Ariola:
        // test6 =
        //   runCC (
        //     do
        //     p <- newPrompt
        //     pushPrompt p $
        //       do
        //             fun <- return (\x -> shiftP p (\f1 -> do
        //                                                   a <- f1 x
        //                                                   return (4 - a)))
        //             arg <- do
        //                     b <- shiftP p (\f2 -> do
        //                                              a <- f2 (return 7)
        //                                              return (2 + a))
        //                       return (return (3 + b))
        //             a <- fun arg
        //             return (5 + a))
        // -- with shift => -9
        // -- with F => -13

        $cc = CCMonad::newPrompt()
            ->flatmap(
                fn ($p) => CCMonad::pushPrompt(
                    $p,
                    CCMonad::create(fn (CCMonad $x) => $this->shiftP(
                        $p,
                        fn ($f1) => call_user_func($f1, $x)->flatmap(fn ($a) => CCMonad::create(4 - $a))
                    ))
                        ->flatmap(
                            // $fun = the shift defined above
                            fn ($fun) => $this->shiftP(
                                $p,
                                fn ($f2) => call_user_func($f2, CCMonad::create(7))
                                    ->flatmap(fn ($a) => CCMonad::create(2 + $a))
                            )
                                ->flatmap(fn ($b) => CCMonad::create(CCMonad::create(3 + $b)))
                                ->flatmap(fn ($arg) => call_user_func($fun, $arg) // arg must be a frame
                                    ->flatmap(fn ($a) => CCMonad::create($a + 5)))
                        )
                )
            )
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(-9, $result);
    }

    public function test7()
    {
        //     test7 = runCC (
        //      do
        //       p <- newPrompt
        //       v <- pushPrompt p $
        //                          do
        //                           v1 <- withSubCont p $ \sk -> pushPrompt p (pushSubCont sk (return 5))
        //                           return $ v1 + 10
        //       return $ v + 20
        //     )
        // -- 35

        $cc = CCMonad::newPrompt()
            ->flatmap(
                fn ($p) => CCMonad::pushPrompt(
                    $p,
                    CCMonad::withSubCont(
                        $p,
                        fn ($sk) => CCMonad::pushPrompt($p, CCMonad::pushSubCont($sk, CCMonad::create(5)))
                    )
                        ->flatmap(fn ($v1) => CCMonad::create($v1 + 10))
                )
                    ->flatmap(fn ($v) => CCMonad::create($v + 20))
            )
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(35, $result);
    }

    public function test8()
    {
        // test8 = runCC (
        //     do
        //      p0 <- newPrompt
        //      p1 <- newPrompt
        //      pushPrompt p0 (do
        //                     v <- shiftP p0 $
        //                          \sk ->
        //                                  do
        //                                  v1 <- sk (sk (return 3))
        //                                  return (100 + v1)
        //                      return (v + 2))
        //      >>= (return . (10 +)))

        //     -- 117
        $cc = CCMonad::newPrompt()
            ->flatmap(
                fn ($p0) => CCMonad::newPrompt()
                    ->flatmap(
                        fn ($p1) => CCMonad::pushPrompt(
                            $p0,
                            $this->shiftP(
                                $p0,
                                fn ($sk) => call_user_func($sk, call_user_func($sk, CCMonad::create(3)))
                                    ->flatmap(fn ($v1) => CCMonad::create(100 + $v1))
                            )
                                ->flatmap(fn ($v) => CCMonad::create($v + 2))
                        )
                            ->flatmap(fn ($x) => CCMonad::create(10 + $x))
                    )
            )
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(117, $result);
    }

    public function test9()
    {
        // test9 = runCC (
        //     do
        //     p0 <- newPrompt
        //     p1 <- newPrompt
        //     pushPrompt p0 (do
        //           v <- shiftP p0 $
        //                \sk ->
        //                   do v1 <- sk (return 3)
        //                      return (100 + v1)
        //           return (v + 2))
        //     >>= (return . (10 +)))
        // -- 115
        $cc = CCMonad::newPrompt()
            ->flatmap(fn ($p0) => CCMonad::newPrompt()
                ->flatmap(fn ($p1) => CCMonad::pushPrompt(
                    $p0,
                    $this->shiftP(
                        $p0,
                        fn ($sk) => call_user_func($sk, CCMonad::create(3))->flatmap(fn ($v1) => CCMonad::create(100 + $v1))
                    )->flatmap(fn ($v) => CCMonad::create($v + 2))
                        ->flatmap(fn ($x) => CCMonad::create(10 + $x))
                )))
        ;
        $result = CCMonad::runCC($cc);
        $this->assertEquals(115, $result);
    }
}
