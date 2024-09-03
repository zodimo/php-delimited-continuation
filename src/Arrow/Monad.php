<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template A
 */
interface Monad
{
    /**
     * @template B
     *
     * @param callable(A):Monad<B> $f
     *
     * @return Monad<B>
     */
    public function flatmap(callable $f): Monad;

    /**
     * Monadic return or applicative pure.
     *
     * @template _A
     *
     * @param _A $a
     *
     * @return Monad<_A>
     */
    public static function pure($a): Monad;

    /**
     * @template B
     *
     * @param callable(A):B $f
     *
     * @return Monad<B>
     */
    public function fmap(callable $f): Monad;
}
