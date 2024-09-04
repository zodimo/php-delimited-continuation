<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\KleisliIOComposition;

interface KleisliCompositionEffectHandlerInterface
{
    /**
     * @template INPUT
     * @template OUTPUT
     * @template ERR
     *
     * @param EffectInterface<INPUT,OUTPUT,ERR> $effect
     *
     * @return KleisliIOComposition<INPUT,OUTPUT,ERR>
     */
    public function handle(EffectInterface $effect, Runtime $runtime): KleisliIOComposition;
}
