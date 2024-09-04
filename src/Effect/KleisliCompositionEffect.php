<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;

/**
 * @template INPUT
 * @template OUTPUT
 * @template ERR
 */
class KleisliCompositionEffect implements EffectInterface
{
    private const namespace = 'kleisli-composition-effect';
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

        throw new \RuntimeException('KleisliCompositionEffect: unknown key:'.$key);
    }

    public function getTag(): string
    {
        return $this->operation->getTag();
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param KleisliEffect<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliCompositionEffect<_INPUT, _OUTPUT, _ERR>
     */
    public static function intializeWith(KleisliEffect $effect): KleisliCompositionEffect
    {
        $tag = self::createTag('initialize-with');

        return new self(Operation::create($tag)->setArg('effect', $effect));
    }

    /**
     * @return KleisliCompositionEffect<INPUT, INPUT, mixed>
     */
    public static function id(): KleisliCompositionEffect
    {
        $tag = self::createTag('id');

        return new self(Operation::create($tag));
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
     * @return KleisliCompositionEffect<_INPUT, _OUTPUT, _ERR>
     */
    public static function arr($f): KleisliCompositionEffect
    {
        $tag = self::createTag('arr');

        return new self(Operation::create($tag)->setArg('f', $f));
    }

    /**
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliCompositionEffect<_INPUT, _OUTPUTF, _ERRF>   $effectF
     * @param KleisliCompositionEffect<_OUTPUTF, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliCompositionEffect<_INPUT, _OUTPUTG, _ERRF|_ERRG>
     */
    public static function compose(KleisliCompositionEffect $effectF, KleisliCompositionEffect $effectG): KleisliCompositionEffect
    {
        $tag = self::createTag('compose');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * Intended for testing.
     */
    public function getArgs(): array
    {
        return $this->operation->getArgs();
    }

    private static function createTag(string $segment): string
    {
        return self::namespace.'.'.$segment;
    }
}
