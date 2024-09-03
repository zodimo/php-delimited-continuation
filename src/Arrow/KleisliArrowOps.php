<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

class KleisliArrowOps
{
    /**
     * first :: a b c -> a (b,d) (c,d).
     *
     * return is the return from monad (a)
     *
     *
     * first (Kleisli f) = Kleisli (\ ~(b,d) -> f b >>= \c -> return (c,d)).
     *
     * f :: a -> m b
     * return of ^ this M
     * M here is IOMonad
     *
     * A piping method first that takes an arrow between two types and
     * converts it into an arrow between tuples. The first elements in
     * the tuples represent the portion of the input and output that is altered,
     * while the second elements are a third type u describing an unaltered
     * portion that bypasses the computation.
     *
     * @template _B b
     * @template _D d
     * @template _E
     *
     * @param KleisliIO<IOMonad, _B, _D, _E> $arrow
     *
     * @return KleisliIO<IOMonad, Tuple<_B, mixed>, Tuple<_D, mixed>, _E>
     */
    public static function first(KleisliIO $arrow): KleisliIO
    {
        /**
         * @var callable(Tuple<_B, mixed>):IOMonad<Tuple<_D, mixed>, _E> $func
         */
        $func = function (Tuple $args) use ($arrow) {
            $input = $args->fst();
            $d = $args->snd();

            return $arrow->run($input)->flatmap(fn ($c) => IOMonad::pure(Tuple::create($c, $d)));
        };

        return KleisliIO::arr($func);
    }

    /**
     * second :: a b c -> a (d,b) (d,c)
     * second = (id ***).
     *
     * @template _B b
     * @template _D d
     * @template _E
     *
     * @param KleisliIO<IOMonad, _B, _D, _E> $arrow
     *
     * @return KleisliIO<IOMonad, Tuple<mixed, _B>, Tuple<mixed, _D>, _E>
     */
    public static function second(KleisliIO $arrow): Arrow
    {
        /**
         * @var callable(Tuple<mixed, _B>):IOMonad<Tuple<mixed, _D>, _E> $func
         */
        $func = function (Tuple $args) use ($arrow) {
            $input = $args->snd();
            $d = $args->fst();

            return $arrow->run($input)->flatmap(fn ($c) => IOMonad::pure(Tuple::create($d, $c)));
        };

        return KleisliIO::arr($func);
    }

    /**
     * ">>>".
     * A composition operator >>> that can attach a second arrow to a first
     * as long as the first function’s output and the second’s input have matching types.
     *
     * -- | Left-to-right composition
     * (>>>) :: Category cat => cat a b -> cat b c -> cat a c
     * f >>> g = g . f
     *
     * @template _B
     * @template _C
     * @template _D
     * @template _EF
     * @template _EG
     *
     * @param KleisliIO<IOMonad, _B, _C, _EF> $f
     * @param KleisliIO<IOMonad, _C, _D, _EG> $g
     *
     * @return KleisliIO<IOMonad, _B, _D, _EF|_EG>
     */
    public static function compose(KleisliIO $f, KleisliIO $g)
    {
        return $f->andThen($g);
    }

    /**
     * "***".
     * A merging operator *** that can take two arrows, possibly with different
     * input and output types, and fuse them into one arrow between two compound types.
     *
     * (***) :: a b c -> a b' c' -> a (b,b') (c,c')
     * f *** g = first f >>> arr swap >>> first g >>> arr swap
     *  where swap ~(x,y) = (y,x)
     *
     * @template _B
     * @template __B
     * @template _C
     * @template __C
     * @template _EF
     * @template _EG
     *
     * @param KleisliIO<IOMonad, _B, _C, _EF>   $f
     * @param KleisliIO<IOMonad, __B, __C, _EG> $g
     *
     * @return KleisliIO<IOMonad, Tuple<_B, __B>, Tuple<_C,  __C>, _EF|_EG>
     */
    public static function merge(KleisliIO $f, KleisliIO $g): KleisliIO
    {
        $swap = function (Tuple $t): Tuple {
            return Tuple::create($t->snd(), $t->fst());
        };

        /**
         * 1:1 translation.
         * first f >>> arr swap >>> first g >>> arr swap.
         */

        // @phpstan-ignore return.type
        return KleisliArrowOps::first($f)->andThen(
            // @phpstan-ignore argument.type
            KleisliIO::arr(fn (Tuple $t) => IOMonad::pure($swap($t)))
        )->andThen(
            // @phpstan-ignore argument.type
            KleisliArrowOps::first($g)->andThen(KleisliIO::arr(fn (Tuple $t) => IOMonad::pure($swap($t))))
        );
    }

    /**
     * "&&&".
     * (&&&) :: a b c -> a b c' -> a b (c,c')
     * f &&& g = arr (\b -> (b,b)) >>> f *** g.
     *
     * @template _B
     * @template _C
     * @template __C
     * @template _EF
     * @template _EG
     *
     * @param KleisliIO<IOMonad, _B, _C, _EF>  $f
     * @param KleisliIO<IOMonad, _B, __C, _EG> $g
     *
     * @return KleisliIO<IOMonad, _B,  Tuple<_C,  __C>, _EF|_EG>
     */
    public static function split(Arrow $f, Arrow $g): KleisliIO
    {
        /**
         * 1:1 translation
         * f &&& g = arr (\b -> (b,b)) >>> f *** g.
         *
         * @phpstan-ignore argument.type
         */
        return KleisliIO::arr(fn ($b) => IOMonad::pure(Tuple::create($b, $b)))->andThen(
            KleisliArrowOps::merge($f, $g)
        );
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _CONDERR
     * @template _THENERR
     * @template _ELSEERR
     *
     * @param KleisliIO<IOMonad, _INPUT, bool, _CONDERR>    $cond
     * @param KleisliIO<IOMonad, _INPUT, _OUTPUT, _THENERR> $then
     * @param KleisliIO<IOMonad, _INPUT, _OUTPUT, _ELSEERR> $else
     *
     * @return KleisliIO<IOMonad, _INPUT, _OUTPUT, _ELSEERR|_THENERR>
     */
    public static function ifThenElse(KleisliIO $cond, KleisliIO $then, KleisliIO $else): KleisliIO
    {
        /**
         * @var callable(_INPUT):IOMonad<_OUTPUT, _ELSEERR|_THENERR>
         */
        $func = function ($input) use ($cond, $then, $else) {
            return $cond->run($input)->match(
                function ($condResult) use ($input, $then, $else) {
                    if ($condResult) {
                        return $then->run($input);
                    }

                    return $else->run($input);
                },
                fn ($err) => IOMonad::fail($err)
            );
        };

        return KleisliIO::arr($func);
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _CHECKERR
     * @template _BODYERR
     *
     * @param KleisliIO<IOMonad, _INPUT,bool, _CHECKERR>   $check
     * @param KleisliIO<IOMonad, _INPUT,_OUTPUT, _BODYERR> $body
     *
     * @return KleisliIO<IOMonad, _INPUT,_OUTPUT, _BODYERR|_CHECKERR>
     */
    public static function whileDo(KleisliIO $check, KleisliIO $body): KleisliIO
    {
        /**
         * @var callable(_INPUT):IOMonad<_OUTPUT, _BODYERR|_CHECKERR>
         */
        $func = function ($value) use ($check, $body) {
            $a = $value;
            while (true) {
                $checkResult = $check->run($a);
                if ($checkResult->isFailure()) {
                    return $checkResult;
                }
                if ($checkResult->unwrapSuccess(fn ($_) => false)) {
                    $bodyResult = $body->run($a);
                    if ($bodyResult->isFailure()) {
                        return $bodyResult;
                    }
                    $a = $bodyResult->unwrapSuccess(fn ($_) => $a);
                } else {
                    break;
                }
            }

            return IOMonad::pure($a);
        };

        return KleisliIO::arr($func);
    }
}
