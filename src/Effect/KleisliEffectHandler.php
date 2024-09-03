<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliArrowOps;
use Zodimo\DCF\Arrow\KleisliIO;

/**
 * reify the kleisli arrow from effect.
 */
class KleisliEffectHandler implements KleisliArrowEffectHandler
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
    public function handle(KleisliArrowEffect $effect, Runtime $runtime): KleisliIO
    {
        $tag = $effect->getTag();

        switch ($tag) {
            case 'kleisli-effect.arr':
                $f = $effect->getArg('f');

                return KleisliIO::arr($f);

            case 'kleisli-effect.id':
                return KleisliIO::id();

            case 'kleisli-effect.first':
                $arrow = $runtime->perform($effect->getArg('effect'));

                // @phpstan-ignore return.type
                return KleisliArrowOps::first($arrow);

            case 'kleisli-effect.second':
                $arrow = $runtime->perform($effect->getArg('effect'));

                // @phpstan-ignore return.type
                return KleisliArrowOps::second($arrow);

            case 'kleisli-effect.compose':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                return KleisliArrowOps::compose($arrowF, $arrowG);

            case 'kleisli-effect.merge':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                // @phpstan-ignore return.type
                return KleisliArrowOps::merge($arrowF, $arrowG);

            case 'kleisli-effect.split':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                // @phpstan-ignore return.type
                return KleisliArrowOps::split($arrowF, $arrowG);

            default:
                throw new \RuntimeException("KleisliEffectHandler: unknown tag: {$tag}");
        }
    }
}
