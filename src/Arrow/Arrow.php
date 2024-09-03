<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template A
 * @template B
 * @template C
 */
interface Arrow
{
    /**
     * @template INPUT
     * @template OUTPUT
     *
     * @param callable(INPUT):OUTPUT $f
     *
     * @return Arrow<mixed, INPUT, OUTPUT>
     */
    public static function arr(callable $f): Arrow;

    /**
     * @return Arrow<A, B, B>
     */
    public static function id(): Arrow;

    /**
     * @param B $value
     *
     * @return C
     */
    public function run($value);
}
