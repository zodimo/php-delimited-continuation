<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\KleisliIOComposition;
use Zodimo\DCF\Arrow\KleisliIOCompositionOps;

class KleisliCompositionEffectHandler implements KleisliCompositionEffectHandlerInterface
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
    public function handle(EffectInterface $effect, Runtime $runtime): KleisliIOComposition
    {
        $tag = $effect->getTag();

        switch ($tag) {
            case 'kleisli-composition-effect.initialize-with':
                $arrowEffect = $effect->getArg('effect');
                $arrow = $runtime->perform($arrowEffect);

                return KleisliIOComposition::intializeWith($arrow);

            case 'kleisli-composition-effect.id':
                return KleisliIOComposition::id();

            case 'kleisli-composition-effect.arr':
                $f = $effect->getArg('f');

                return KleisliIOComposition::arr($f);

            case 'kleisli-composition-effect.compose':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                return KleisliIOCompositionOps::compose($arrowF, $arrowG);

            default:
                throw new \RuntimeException("KleisliCompositionEffectHandler: unknown tag: {$tag}");
        }
    }
}
