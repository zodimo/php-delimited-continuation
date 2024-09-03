<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template VALUE
 */
interface Monad
{
    /**
     * @template OUTPUTF
     *
     * @param callable(VALUE):Monad<OUTPUTF> $f
     *
     * @return Monad<OUTPUTF>
     */
    public function flatmap(callable $f): Monad;

    /**
     * Monadic return or applicative pure.
     *
     * @template _VALUE
     *
     * @param _VALUE $a
     *
     * @return Monad<_VALUE>
     */
    public static function pure($a): Monad;

    /**
     * @template OUTPUTF
     *
     * @param callable(VALUE):OUTPUTF $f
     *
     * @return Monad<OUTPUTF>
     */
    public function fmap(callable $f): Monad;
}
