<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template M
 * @template INPUT
 * @template OUTPUT
 */
interface Arrow
{
    /**
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param callable(_INPUT):_OUTPUT $f
     *
     * @return Arrow<M, _INPUT, _OUTPUT>
     */
    public static function arr(callable $f): Arrow;

    /**
     * @return Arrow<M, INPUT, INPUT>
     */
    public static function id(): Arrow;

    /**
     * @param INPUT $value
     *
     * @return OUTPUT
     */
    public function run($value);
}
