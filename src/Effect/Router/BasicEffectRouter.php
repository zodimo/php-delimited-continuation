<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect\Router;

use Zodimo\Arrow\KleisliIO;
use Zodimo\BaseReturn\Option;
use Zodimo\DCF\Effect\EffectInterface;
use Zodimo\DCF\Effect\EffectRouter;

class BasicEffectRouter implements EffectRouter
{
    /**
     * @var array<string, KleisliEffectHandlerInterface>
     */
    private array $handlers;

    /**
     * @param array<string, KleisliEffectHandlerInterface> $handlers
     */
    private function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * @param array<string, KleisliEffectHandlerInterface> $handlers
     */
    public static function create(array $handlers)
    {
        return new self($handlers);
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param EffectInterface<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliIO<_INPUT, _OUTPUT, _ERR>
     */
    public function perform(EffectInterface $effect): KleisliIO
    {
        $tag = $effect->getTag();
        $router = $this;

        return $this->getHandlerForEffect(get_class($effect))
            ->match(
                function ($handler) use ($effect, $router) {
                    return $handler->handle($effect, $router);
                },
                function () use ($tag) {
                    throw new \RuntimeException(__CLASS__." :Handler for {$tag} not found.");
                }
            )
        ;
    }

    /**
     * @param class-string $effectClass
     *
     * @return Option<KleisliEffectHandlerInterface>
     */
    public function getHandlerForEffect(string $effectClass): Option
    {
        if (key_exists($effectClass, $this->handlers)) {
            return Option::some($this->handlers[$effectClass]);
        }

        return Option::none();
    }
}
