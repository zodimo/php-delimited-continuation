<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\Arrow\KleisliIO;
use Zodimo\Arrow\KleisliIOOps;
use Zodimo\DCF\Effect\Router\KleisliEffectHandlerInterface;

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
     * @param EffectInterface<INPUT,OUTPUT,ERR> $effect
     *
     * @return KleisliIO<INPUT,OUTPUT,ERR>
     */
    public function handle(EffectInterface $effect, EffectRouter $runtime): KleisliIO
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

            case 'kleisli-effect.composition':
                $effects = $effect->getArg('effects');
                $arrows = array_map(fn ($eff) => $runtime->perform($eff), $effects);

                return array_reduce($arrows, function (KleisliIO $acc, $item) {
                    return $acc->andThen($item);
                }, KleisliIO::id());

            case 'kleisli-effect.merge':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                // @phpstan-ignore return.type
                // @phpstan-ignore return.type
                return KleisliIOOps::merge($arrowF, $arrowG);

            case 'kleisli-effect.split':
                $arrowF = $runtime->perform($effect->getArg('effectF'));
                $arrowG = $runtime->perform($effect->getArg('effectG'));

                // @phpstan-ignore return.type
                return KleisliIOOps::split($arrowF, $arrowG);

            case 'kleisli-effect.lift-pure':
                $f = $effect->getArg('f');

                return KleisliIO::liftPure($f);

            case 'kleisli-effect.lift-impure':
                $f = $effect->getArg('f');

                return KleisliIO::liftImpure($f);

            case 'kleisli-effect.bracket':
                $acquire = $runtime->perform($effect->getArg('acquire'));
                $during = $runtime->perform($effect->getArg('during'));
                $release = $runtime->perform($effect->getArg('release'));

                return KleisliIOOps::bracket($acquire, $during, $release);

            case 'kleisli-effect.flatmap':
                $thisEffect = $effect->getArg('effect');
                $thisArrow = $runtime->perform($thisEffect);

                $f = $effect->getArg('f');

                return $thisArrow->flatMap(fn ($value) => $runtime->perform(call_user_func($f, $value)));

            case 'kleisli-effect.stub-input':
                $thisEffect = $effect->getArg('effect');
                $input = $effect->getArg('input');
                $thisArrow = $runtime->perform($thisEffect);

                return KleisliIO::arr(fn () => $thisArrow->run($input));

            case 'kleisli-effect.if-then-else':
                $cond = $runtime->perform($effect->getArg('cond'));
                $then = $runtime->perform($effect->getArg('then'));
                $else = $runtime->perform($effect->getArg('else'));

                return KleisliIOOps::ifThenElse($cond, $then, $else);

            case 'kleisli-effect.choice':
                $onLeft = $runtime->perform($effect->getArg('onLeft'));
                $onRight = $runtime->perform($effect->getArg('onRight'));

                // @phpstan-ignore return.type
                return KleisliIOOps::choice($onLeft, $onRight);

            case 'kleisli-effect.prompt':
                $thisEffect = $effect->getArg('effect');

                return $runtime->perform(DelimitedEffectOps::prompt($thisEffect));

            case 'kleisli-effect.reset':
                $thisEffect = $effect->getArg('effect');

                return $runtime->perform(DelimitedEffectOps::reset($thisEffect));

            default:
                throw new \RuntimeException("KleisliEffectHandler: unknown tag: {$tag}");
        }
    }
}
