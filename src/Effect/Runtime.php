<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Arrow\KleisliIOComposition;

interface Runtime
{
    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param EffectInterface<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliIO<IOMonad, _INPUT, _OUTPUT, _ERR>|KleisliIOComposition< _INPUT, _OUTPUT, _ERR>
     */
    public function perform(EffectInterface $effect);
}
