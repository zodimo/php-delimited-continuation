<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\Arrow\KleisliIO;

interface KleisliEffectHandlerInterface
{
    /**
     * @template INPUT
     * @template OUTPUT
     * @template ERR
     *
     * @param EffectInterface<INPUT,OUTPUT,ERR> $effect
     *
     * @return KleisliIO<INPUT,OUTPUT,ERR>
     */
    public function handle(EffectInterface $effect, Runtime $runtime): KleisliIO;
}
