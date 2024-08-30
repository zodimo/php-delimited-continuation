<?php

declare(strict_types=1);

namespace Zodimo\DCF\Frame;

/**
 * @template ANS
 * @template A
 * @template B
 */
class Frame
{
    // newtype Frame ans a b = Frame (a -> CC ans b)

    private $f;

    /**
     * @param callable(A):CCMonad<ANS,B> $f
     */
    public function __construct($f)
    {
        $this->f = $f;
    }

    /**
     * @param callable(A):CCMonad<ANS,B> $f
     *
     * @return Frame<ANS, A, B>
     */
    public static function create(callable $f): Frame
    {
        return new self($f);
    }

    /**
     * @param A $a
     *
     * @return CCMonad<ANS, B>
     */
    public function run($a)
    {
        return call_user_func($this->f, $a);
    }
}
