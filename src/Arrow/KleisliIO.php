<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * it assumes that a handles exists to perform A->E[B].
 *
 * @template M
 * @template INPUT
 * @template OUTPUT
 * @template ERR
 *
 * @implements Arrow<KleisliIO, INPUT, OUTPUT>
 */
class KleisliIO implements Arrow
{
    private $f;

    /**
     * @param callable(INPUT):IOMonad<OUTPUT,ERR> $f
     */
    private function __construct($f)
    {
        $this->f = $f;
    }

    /**
     * instance Monad m => Monad (Kleisli m a) where
     * Kleisli f >>= k = Kleisli $ \x -> f x >>= \a -> runKleisli (k a) x.
     *
     * @template _OUTPUTK
     * @template _ERRK
     *
     * @param KleisliIO<IOMonad, OUTPUT,_OUTPUTK, _ERRK> $k
     *
     * @return KleisliIO<IOMonad, INPUT,_OUTPUTK, _ERRK|ERR>
     */
    public function andThen(KleisliIO $k): KleisliIO
    {
        $that = $this;

        /**
         * @var callable(INPUT):IOMonad<_OUTPUTK, mixed> $func
         */
        $func = function ($input) use ($that, $k) {
            return $that->run($input)->flatmap(fn ($value) => $k->run($value));
        };

        return new KleisliIO($func);
    }

    /**
     * f = B=>M[C].
     *
     * @template _INPUT
     * @template _OUPUT
     * @template _ERR
     *
     * @param callable(_INPUT):IOMonad<_OUPUT, _ERR> $f
     *
     * @return KleisliIO<IOMonad, _INPUT, _OUPUT, _ERR>
     */
    public static function arr(callable $f): KleisliIO
    {
        return new KleisliIO($f);
    }

    /**
     * @return KleisliIO<IOMonad, M, M, mixed>
     */
    public static function id(): KleisliIO
    {
        $func = fn ($a) => IOMonad::pure($a);

        return new KleisliIO($func);
    }

    /**
     * @param INPUT $value
     *
     * @return IOMonad<OUTPUT, ERR>
     */
    public function run($value)
    {
        return call_user_func($this->f, $value);
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param callable(_INPUT):_OUTPUT $f
     *
     * @return KleisliIO<IOMonad, _INPUT, _OUTPUT, mixed>
     */
    public static function impure($f): KleisliIO
    {
        /**
         * @var callable(_INPUT):IOMonad<_OUTPUT, mixed>
         */
        $try = function ($a) use ($f) {
            try {
                return IOMonad::pure(call_user_func($f, $a));
            } catch (\Throwable $e) {
                return IOMonad::fail($e);
            }
        };

        return self::arr($try);
    }
}
