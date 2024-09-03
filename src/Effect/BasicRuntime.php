<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\BaseReturn\Option;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;

class BasicRuntime implements Runtime
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
     * @param KleisliEffectInterface<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliIO<IOMonad, _INPUT, _OUTPUT, _ERR>
     */
    public function perform(KleisliEffectInterface $effect)
    {
        $tag = $effect->getTag();
        $runtime = $this;

        return $this->getHandlerForEffect(get_class($effect))
            ->match(
                function ($handler) use ($effect, $runtime) {
                    return $handler->handle($effect, $runtime);
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
