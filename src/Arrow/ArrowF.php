<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template A
 * @template B
 * @template C
 *
 * @implements Arrow<A, B, C>
 */
class ArrowF implements Arrow
{
    private $f;

    /**
     * @param callable $f
     */
    private function __construct($f)
    {
        $this->f = $f;
    }

    /**
     * @template INPUT
     * @template OUTPUT
     *
     * @param callable(INPUT):OUTPUT $f
     *
     * @return ArrowF<ArrowF, INPUT, OUTPUT>
     */
    public static function arr(callable $f): ArrowF
    {
        return new ArrowF($f);
    }

    /**
     * @param B $value
     *
     * @return C
     */
    public function run($value)
    {
        return call_user_func($this->f, $value);
    }

    /**
     * @return ArrowF<ArrowF, B, B>
     */
    public static function id(): Arrow
    {
        /**
         * @var callable(B):B
         */
        $id = function ($x) {
            return $x;
        };

        return new ArrowF($id);
    }
}
