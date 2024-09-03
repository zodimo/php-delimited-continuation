<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;

interface Runtime
{
    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param KleisliArrowEffect<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliIO<IOMonad, _INPUT, _OUTPUT, _ERR>
     */
    public function perform(KleisliArrowEffect $effect);
}
