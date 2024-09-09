<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect\Router;

use Zodimo\Arrow\KleisliIO;
use Zodimo\DCF\Effect\EffectInterface;
use Zodimo\DCF\Effect\EffectRouter;

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
    public function handle(EffectInterface $effect, EffectRouter $router): KleisliIO;
}
