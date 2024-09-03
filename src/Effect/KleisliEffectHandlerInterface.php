<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;

interface KleisliEffectHandlerInterface
{
    /**
     * @template INPUT
     * @template OUTPUT
     * @template ERR
     *
     * @param KleisliEffectInterface<INPUT,OUTPUT,ERR> $effect
     *
     * @return KleisliIO<IOMonad,INPUT,OUTPUT,ERR>
     */
    public function handle(KleisliEffectInterface $effect, Runtime $runtime): KleisliIO;
}
