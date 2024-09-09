<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect\Effects;

use Zodimo\Arrow\KleisliIO;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\EffectInterface;
use Zodimo\DCF\Effect\EffectRouter;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\Router\KleisliEffectHandlerInterface;

class StateEffectHandler implements KleisliEffectHandlerInterface
{
    private $state;

    public function __construct($state)
    {
        $this->state = $state;
    }

    public function handle(EffectInterface $effect, EffectRouter $router): KleisliIO
    {
        if (!$effect instanceof StateEffect) {
            throw new \InvalidArgumentException('Unsupported effect: '.get_class($effect));
        }

        $tag = $effect->getTag();

        switch ($tag) {
            case 'state-effect.get':
                return $router->perform(
                    // @phpstan-ignore argument.type
                    KleisliEffect::arr(fn ($input) => IOMonad::pure(Tuple::create($input, $this->state))),
                );

            case 'state-effect.set':
                $this->state = $effect->getArg('state');

                return $router->perform(KleisliEffect::id());

            default:
                throw new \RuntimeException("Unknown StateEffect tag: {$tag}");
        }
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }
}
