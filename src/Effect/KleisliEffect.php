<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\Tuple;

/**
 * @template INPUT
 * @template OUTPUT
 * @template ERR
 */
class KleisliEffect implements KleisliArrowEffect
{
    private const namespace = 'kleisli-effect';
    private Operation $operation;

    private function __construct(Operation $operation)
    {
        $this->operation = $operation;
    }

    public function getArg($key)
    {
        $args = $this->operation->getArgs();
        if (key_exists($key, $args)) {
            return $this->operation->getArg($key);
        }

        throw new \RuntimeException('KleisliEffect: unknown key:'.$key);
    }

    /**
     * F must also be an effect.. not just a deferred closure ?
     *
     * @template _IN
     * @template _OUT
     * @template _ERR
     *
     * @param callable(_IN):IOMonad<_OUT, _ERR> $f
     *
     * @return KleisliEffect<_IN, _OUT, _ERR>
     */
    public static function arr($f): self
    {
        $tag = self::createTag('arr');

        return new self(Operation::create($tag)->setArg('f', $f));
    }

    /**
     * @return KleisliEffect<INPUT, INPUT, mixed>
     */
    public static function id(): self
    {
        $tag = self::createTag('id');

        return new self(Operation::create($tag));
    }

    /**
     * ARROW OPS.
     */

    /**
     * first :: a b c -> a (b,d) (c,d).
     * first = (*** id).
     *
     * A piping method first that takes an arrow between two types and
     * converts it into an arrow between tuples. The first elements in
     * the tuples represent the portion of the input and output that is altered,
     * while the second elements are a third type u describing an unaltered
     * portion that bypasses the computation.
     *
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param KleisliEffect<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliEffect<Tuple<_INPUT, mixed>, Tuple<_OUTPUT, mixed>, _ERR>
     */
    public static function first(KleisliEffect $effect): self
    {
        $tag = self::createTag('first');

        return new self(Operation::create($tag)->setArg('effect', $effect));
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param KleisliEffect<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliEffect<Tuple<mixed, _INPUT>, Tuple< mixed, _OUTPUT>, _ERR>
     */
    public static function second(KleisliEffect $effect): self
    {
        $tag = self::createTag('second');

        return new self(Operation::create($tag)->setArg('effect', $effect));
    }

    /**
     * @template _B
     * @template _C
     * @template _D
     * @template _EF
     * @template _EG
     *
     * @param KleisliEffect<_B, _C, _EF>  $effectF
     * @param KleisliEffect< _C, _D, _EG> $effectG
     *
     * @return KleisliEffect<_B, _D, _EF|_EG>
     */
    public static function compose(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('compose');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * @template _B
     * @template __B
     * @template _C
     * @template __C
     * @template _EF
     * @template _EG
     *
     * @param KleisliEffect< _B, _C, _EF>   $effectF
     * @param KleisliEffect< __B, __C, _EG> $effectG
     *
     * @return KleisliEffect< Tuple<_B, __B>, Tuple<_C,  __C>, _EF|_EG>
     */
    public static function merge(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('merge');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * @template _B
     * @template _C
     * @template __C
     * @template _EF
     * @template _EG
     *
     * @param KleisliEffect<  _B, _C, _EF>  $effectF
     * @param KleisliEffect<  _B, __C, _EG> $effectG
     *
     * @return KleisliEffect<  _B,  Tuple<_C,  __C>, _EF|_EG>
     */
    public static function split(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('split');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    public function toString(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return [
            '_tag' => $this->operation->getTag(),
            'args' => $this->operation->getArgs(),
        ];
    }

    public function getTag(): string
    {
        return $this->operation->getTag();
    }

    private static function createTag(string $segment): string
    {
        return self::namespace.'.'.$segment;
    }
}
