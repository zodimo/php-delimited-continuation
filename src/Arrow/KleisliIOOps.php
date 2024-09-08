<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

use Zodimo\BaseReturn\Either;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\BaseReturn\Tuple;

class KleisliIOOps
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
     * Alias for andThen on KleisliIO.
     *
     * ">>>".
     * A composition operator >>> that can attach a second arrow to a first
     * as long as the first function’s output and the second’s input have matching types.
     *
     * -- | Left-to-right composition
     * (>>>) :: Category cat => cat a b -> cat b c -> cat a c
     * f >>> g = g . f
     *
     * @template _A
     * @template _B
     * @template _C
     * @template _EF
     * @template _EG
     *
     * @param KleisliIO<IOMonad, _A, _B, _EF> $f
     * @param KleisliIO<IOMonad, _B, _C, _EG> $g
     *
     * @return KleisliIO<IOMonad, _A, _C, _EF|_EG>
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
     * @template _INPUTF
     * @template _INPUTG
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliIO<IOMonad, _INPUTF, _OUTPUTF, _ERRF> $f
     * @param KleisliIO<IOMonad, _INPUTG, _OUTPUTG, _ERRG> $g
     *
     * @return KleisliIO<IOMonad, Tuple<_INPUTF, _INPUTG>, Tuple<_OUTPUTF,_OUTPUTG>, _ERRF|_ERRG>
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
        return KleisliIOOps::first($f)->andThen(
            // @phpstan-ignore argument.type
            KleisliIO::arr(fn (Tuple $t) => IOMonad::pure($swap($t)))
        )->andThen(
            // @phpstan-ignore argument.type
            KleisliIOOps::first($g)->andThen(KleisliIO::arr(fn (Tuple $t) => IOMonad::pure($swap($t))))
        );
    }

    /**
     * "&&&".
     * (&&&) :: a b c -> a b c' -> a b (c,c')
     * f &&& g = arr (\b -> (b,b)) >>> f *** g.
     *
     * @template _INPUT
     * @template _OUPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliIO<IOMonad, _INPUT, _OUPUTF, _ERRF>  $f
     * @param KleisliIO<IOMonad, _INPUT, _OUTPUTG, _ERRG> $g
     *
     * @return KleisliIO<IOMonad, _INPUT,  Tuple<_OUPUTF,  _OUTPUTG>, _ERRF|_ERRG>
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
            KleisliIOOps::merge($f, $g)
        );
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _LEFTERR
     * @template _RIGHTERR
     *
     * @param KleisliIO<IOMonad, _INPUT, _OUTPUT, _LEFTERR>  $onLeft
     * @param KleisliIO<IOMonad, _INPUT, _OUTPUT, _RIGHTERR> $onRight
     *
     * @return KleisliIO<IOMonad, Either<_INPUT, _INPUT>, _OUTPUT, _LEFTERR|_RIGHTERR>
     */
    public static function choice(KleisliIO $onLeft, KleisliIO $onRight): KleisliIO
    {
        /**
         * @var callable(Either<_INPUT, _INPUT>):IOMonad<_OUTPUT, _LEFTERR|_RIGHTERR>
         */
        $func = function (Either $input) use ($onLeft, $onRight) {
            return $input->match(
                fn ($left) => $onLeft->run($left),
                fn ($right) => $onRight->run($right)
            );
        };

        // @phpstan-ignore return.type
        // @phpstan-ignore return.type
        return KleisliIO::arr($func);
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

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     * @template _OUTPUTF
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliIO<IOMonad,_INPUT, _OUTPUT, _ERR>    $acquire
     * @param KleisliIO<IOMonad,_OUTPUT, _OUTPUTF, _ERRF> $during
     * @param KleisliIO<IOMonad,_OUTPUT, null, _ERRG>     $release
     *
     * @return KleisliIO<IOMonad,_INPUT, _OUTPUTF, _ERR|_ERRF|_ERRG|\Throwable>
     */
    public static function bracket(KleisliIO $acquire, KleisliIO $during, KleisliIO $release): KleisliIO
    {
        /**
         * @var callable(_INPUT):IOMonad<_OUTPUTF, _ERR|_ERRF|_ERRG|\Throwable>
         */
        $func = function ($input) use ($acquire, $during, $release) {
            $acquireResult = $acquire->run($input);

            return $acquireResult->flatmap(
                // @phpstan-ignore argument.type
                // @phpstan-ignore argument.type
                function ($acquiredResource) use ($during, $release) {
                    try {
                        $duringResult = $during->run($acquiredResource);
                        $releaseResult = $release->run($acquiredResource);

                        return IOMonad::pure(Tuple::create($duringResult, $releaseResult));
                    } catch (\Throwable $e) {
                        // fail safe
                        $duringResult = IOMonad::fail($e);
                        $releaseResult = $release->run($acquiredResource);

                        return IOMonad::pure(Tuple::create($duringResult, $releaseResult));
                    }
                }
            );
        };

        return KleisliIO::arr($func);
    }
}
