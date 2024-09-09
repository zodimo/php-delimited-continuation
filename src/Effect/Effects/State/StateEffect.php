<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect\Effects\State;

use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\EffectInterface;
use Zodimo\DCF\Effect\Operation;

/**
 * @template INPUT
 * @template OUTPUT
 * @template STATE
 * @template ERR
 *
 * @implements EffectInterface<INPUT, OUTPUT, ERR>
 */
class StateEffect implements EffectInterface
{
    private const namespace = 'state-effect';
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

        throw new \RuntimeException('StateEffect: unknown key:'.$key);
    }

    /**
     * @template _INPUT of mixed
     * @template _STATE of mixed
     *
     * @return StateEffect<_INPUT, Tuple<_INPUT, _STATE>,_STATE, mixed>
     *
     * @phpstan-ignore method.templateTypeNotInParameter, method.templateTypeNotInParameter
     */
    public static function get(): self
    {
        $tag = self::createTag('get');

        return new self(Operation::create($tag));
    }

    public static function createTag(string $segment): string
    {
        return self::namespace.'.'.$segment;
    }

    /**
     * @template _INPUT of mixed
     * @template _STATE of mixed
     *
     * @param _STATE $state
     *
     * @return StateEffect<_INPUT, _INPUT, mixed>
     *
     * @phpstan-ignore method.templateTypeNotInParameter ,generics.lessTypes
     */
    public static function set($state): StateEffect
    {
        $tag = self::createTag('set');

        // @phpstan-ignore return.type
        return new self(Operation::create($tag)->setArg('state', $state));
    }

    public function getTag(): string
    {
        return $this->operation->getTag();
    }
}
