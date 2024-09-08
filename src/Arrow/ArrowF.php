<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template M
 * @template INPUT
 * @template OUTPUT
 *
 * @implements Arrow<M, INPUT, OUTPUT>
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
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param callable(_INPUT):_OUTPUT $f
     *
     * @return ArrowF<ArrowF, _INPUT, _OUTPUT>
     */
    public static function arr(callable $f): ArrowF
    {
        return new ArrowF($f);
    }

    /**
     * @param INPUT $value
     *
     * @return OUTPUT
     */
    public function run($value)
    {
        return call_user_func($this->f, $value);
    }

    /**
     * @return ArrowF<ArrowF, INPUT, INPUT>
     */
    public static function id(): Arrow
    {
        /**
         * @var callable(INPUT):INPUT
         */
        $id = function ($x) {
            return $x;
        };

        return new ArrowF($id);
    }
}
