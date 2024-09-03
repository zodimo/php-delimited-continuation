<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;

interface KleisliArrowEffectHandler
{
    /**
     * @template INPUT
     * @template OUPUT
     * @template ERR
     *
     * @param KleisliArrowEffect<INPUT,OUPUT,ERR> $effect
     *
     * @return KleisliIO<IOMonad,INPUT,OUPUT,ERR>
     */
    public function handle(KleisliArrowEffect $effect, Runtime $runtime): KleisliIO;
}
