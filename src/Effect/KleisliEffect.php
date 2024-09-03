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
class KleisliEffect implements KleisliEffectInterface
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
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param callable(_INPUT):IOMonad<_OUTPUT, _ERR> $f
     *
     * @return KleisliEffect<_INPUT, _OUTPUT, _ERR>
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
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect<_INPUT, _OUTPUTF, _ERRF>   $effectF
     * @param KleisliEffect<_OUTPUTF, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect<_INPUT, _OUTPUTG, _ERRF|_ERRG>
     */
    public static function compose(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('compose');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * @template _INPUTF
     * @template _INPUTG
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect< _INPUTF, _OUTPUTF, _ERRF> $effectF
     * @param KleisliEffect< _INPUTG, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect< Tuple<_INPUTF, _INPUTG>, Tuple<_OUTPUTF,  _OUTPUTG>, _ERRF|_ERRG>
     */
    public static function merge(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('merge');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect<  _INPUT, _OUTPUTF, _ERRF> $effectF
     * @param KleisliEffect<  _INPUT, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect<  _INPUT,  Tuple<_OUTPUTF,  _OUTPUTG>, _ERRF|_ERRG>
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
