<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Arrow\KleisliIOComposition;
use Zodimo\DCF\Arrow\KleisliIOOps;

/**
 * reify the kleisli arrow from effect.
 */
class KleisliEffectHandler implements KleisliEffectHandlerInterface
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
    public function handle(KleisliEffectInterface $effect, Runtime $runtime): KleisliIO
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
                return KleisliIOOps::first($arrow);

            case 'kleisli-effect.second':
                $arrow = $runtime->perform($effect->getArg('effect'));

                // @phpstan-ignore return.type
                return KleisliIOOps::second($arrow);

            case 'kleisli-effect.compose':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                return KleisliIOOps::compose($arrowF, $arrowG);

            case 'kleisli-effect.composition':
                $effects = $effect->getArg('effects');
                $arrows = array_map(fn ($eff) => $runtime->perform($eff), $effects);

                /**
                 * @var KleisliIOComposition $composition
                 */
                $composition = array_reduce($arrows, function ($acc, $item) {
                    return $acc->addArrow($item);
                }, KleisliIOComposition::id());

                return $composition->asKleisliIO();

            case 'kleisli-effect.merge':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                // @phpstan-ignore return.type
                return KleisliIOOps::merge($arrowF, $arrowG);

            case 'kleisli-effect.split':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                // @phpstan-ignore return.type
                return KleisliIOOps::split($arrowF, $arrowG);

            default:
                throw new \RuntimeException("KleisliEffectHandler: unknown tag: {$tag}");
        }
    }
}
