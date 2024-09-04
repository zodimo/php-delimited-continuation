<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

/**
 * @template INPUT
 * @template OUTPUT
 * @template ERR
 */
interface EffectInterface
{
    /**
     * @param int|string $name
     *
     * @return mixed
     */
    public function getArg($name);

    public function getTag(): string;
}
