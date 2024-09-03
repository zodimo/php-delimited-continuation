<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template LEFT
 * @template RIGHT
 */
class Either implements Monad
{
    private string $_tag;
    private $value;

    private function __construct(string $tag, $value)
    {
        $this->_tag = $tag;
        $this->value = $value;
    }

    /**
     * @template _LEFT
     *
     * @param _LEFT $value
     *
     * @return Either<_LEFT, mixed>
     */
    public static function left($value): Either
    {
        return new Either('left', $value);
    }

    /**
     * @template _RIGHT
     *
     * @param _RIGHT $value
     *
     * @return Either<mixed, _RIGHT>
     */
    public static function right($value): Either
    {
        return new Either('right', $value);
    }

    public function match(callable $onLeft, callable $onRight)
    {
        if ($this->isLeft()) {
            return call_user_func($onLeft, $this->value);
        }

        return call_user_func($onRight, $this->value);
    }

    public function isLeft(): bool
    {
        return 'lefts' === $this->_tag;
    }

    public function isRight(): bool
    {
        return 'right' === $this->_tag;
    }

    /**
     * @template _RIGHT
     *
     * @param callable(RIGHT):_RIGHT $f
     *
     * @return Either<LEFT, _RIGHT>
     */
    public function flatmap(callable $f): Either
    {
        if ($this->isLeft()) {
            return $this;
        }

        return call_user_func($f, $this->value);
    }

    /**
     * @template A
     *
     * @param A $a
     *
     * @return Either<mixed,A>
     */
    public static function pure($a): Either
    {
        return Either::right($a);
    }

    /**
     * @template B
     *
     * @param callable(RIGHT):B $f
     *
     * @return Either<LEFT,B>
     */
    public function fmap(callable $f): Either
    {
        if ($this->isLeft()) {
            return $this;
        }

        return Either::right(call_user_func($f, $this->value));
    }
}
