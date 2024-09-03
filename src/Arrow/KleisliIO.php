<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

use Zodimo\BaseReturn\Result;

/**
 * it assumes that a handles exists to perform A->E[B].
 *
 * @template A
 * @template B
 * @template C
 * @template E
 *
 * @implements Arrow<KleisliIO, B, C>
 */
class KleisliIO implements Arrow
{
    private $f;

    /**
     * @param callable(B):IOMonad<C,E> $f
     */
    private function __construct($f)
    {
        $this->f = $f;
    }

    /**
     * instance Monad m => Monad (Kleisli m a) where
     * Kleisli f >>= k = Kleisli $ \x -> f x >>= \a -> runKleisli (k a) x.
     *
     * @template D
     * @template _E
     *
     * @param KleisliIO<IOMonad, C,D, _E> $k
     *
     * @return KleisliIO<IOMonad, B,D, _E|E>
     */
    public function andThen(KleisliIO $k): KleisliIO
    {
        $that = $this;

        /**
         * @var callable(B):IOMonad<D, mixed> $func
         */
        $func = function ($input) use ($that, $k) {
            return $that->run($input)->flatmap(fn ($value) => $k->run($value));
        };

        return new KleisliIO($func);
    }

    /**
     * f = B=>M[C].
     *
     * @template _B
     * @template _C
     * @template _E
     *
     * @param callable(_B):IOMonad<_C, _E> $f
     *
     * @return KleisliIO<IOMonad, _B, _C, _E>
     */
    public static function arr(callable $f): KleisliIO
    {
        return new KleisliIO($f);
    }

    /**
     * @return KleisliIO<IOMonad, A, A, mixed>
     */
    public static function id(): KleisliIO
    {
        $func = fn ($a) => IOMonad::pure($a);

        return new KleisliIO($func);
    }

    /**
     * @param B $value
     *
     * @return IOMonad<C, E>
     */
    public function run($value)
    {
        return call_user_func($this->f, $value);
    }

    /**
     * @template _B
     * @template _C
     *
     * @param callable(_B):_C $f
     *
     * @return KleisliIO<IOMonad, _B, _C, mixed>
     */
    public function impure($f): KleisliIO
    {
        /**
         * @var callable(_B):IOMonad<_C, mixed>
         */
        $try = function ($a) use ($f) {
            try {
                return IOMonad::pure(Result::succeed(call_user_func($f, $a)));
            } catch (\Throwable $e) {
                return IOMonad::pure(Result::fail($e));
            }
        };

        return self::arr($try);
    }
}
