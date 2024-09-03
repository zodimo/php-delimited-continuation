<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

use Zodimo\BaseReturn\Result;

/**
 * A represent the value of a successful computation
 * E represent the value of a failed computation.
 *
 * @template A
 * @template E
 *
 * @implements Monad<Result<A, E>>
 */
class IOMonad implements Monad
{
    /**
     * @var Result<A, E>
     */
    private Result $_result;

    private function __construct(Result $result)
    {
        $this->_result = $result;
    }

    /**
     * @template B
     * @template _E
     *
     * @param callable(A):IOMonad<B, _E> $f
     *
     * @return IOMonad<B, _E>>|IOMonad<A, E>>
     */
    public function flatmap(callable $f): Monad
    {
        return $this->_result->match(
            fn ($value) => call_user_func($f, $value),
            fn ($_) => $this
        );
    }

    /**
     * @template B
     *
     * @param callable(A):B $f
     *
     * @return IOMonad<B, E>>
     */
    public function fmap(callable $f): Monad
    {
        return $this->_result->match(
            fn ($value) => IOMonad::pure(call_user_func($f, $value)),
            fn ($_) => $this
        );
    }

    /**
     * @template _A
     *
     * @param _A $a
     *
     * @return IOMonad<_A, mixed>>
     */
    public static function pure($a): Monad
    {
        return new self(Result::succeed($a));
    }
}
