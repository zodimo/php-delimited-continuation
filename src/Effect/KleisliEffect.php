<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\BaseReturn\Option;
use Zodimo\DCF\Arrow\Either;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\Tuple;

/**
 * @template INPUT
 * @template OUTPUT
 * @template ERR
 */
class KleisliEffect implements EffectInterface
{
    private const namespace = 'kleisli-effect';
    private Operation $operation;

    private function __construct(Operation $operation)
    {
        $this->operation = $operation;
    }

    public function getArg($key)
    {
        $args = $this->operation->getArgs();
        if (key_exists($key, $args)) {
            return $this->operation->getArg($key);
        }

        throw new \RuntimeException('KleisliEffect: unknown key:'.$key);
    }

    /**
     * Intended for testing.
     */
    public function getArgs(): array
    {
        return $this->operation->getArgs();
    }

    /**
     * F must also be an effect.. not just a deferred closure ?
     *
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param callable(_INPUT):IOMonad<_OUTPUT, _ERR> $f
     *
     * @return KleisliEffect<_INPUT, _OUTPUT, _ERR>
     */
    public static function arr($f): self
    {
        $tag = self::createTag('arr');

        return new self(Operation::create($tag)->setArg('f', $f));
    }

    /**
     * @return KleisliEffect<INPUT, INPUT, mixed>
     */
    public static function id(): self
    {
        $tag = self::createTag('id');

        return new self(Operation::create($tag));
    }

    /**
     * ARROW OPS.
     */

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param KleisliEffect<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliEffect<Tuple<_INPUT, mixed>, Tuple<_OUTPUT, mixed>, _ERR>
     */
    public static function first(KleisliEffect $effect): self
    {
        $tag = self::createTag('first');

        return new self(Operation::create($tag)->setArg('effect', $effect));
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _ERR
     *
     * @param KleisliEffect<_INPUT, _OUTPUT, _ERR> $effect
     *
     * @return KleisliEffect<Tuple<mixed, _INPUT>, Tuple< mixed, _OUTPUT>, _ERR>
     */
    public static function second(KleisliEffect $effect): self
    {
        $tag = self::createTag('second');

        return new self(Operation::create($tag)->setArg('effect', $effect));
    }

    /**
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect<_INPUT, _OUTPUTF, _ERRF>   $effectF
     * @param KleisliEffect<_OUTPUTF, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect<_INPUT, _OUTPUTG, _ERRF|_ERRG>
     */
    public static function compose(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $composeTag = self::createTag('compose');
        $compositionTag = self::createTag('composition');

        $isEff = fn (string $tag): bool => $composeTag !== $tag and $compositionTag !== $tag;
        $isCompose = fn (string $tag): bool => $composeTag == $tag;
        $isComposition = fn (string $tag): bool => $compositionTag == $tag;
        $fTag = $effectF->getTag();
        $gTag = $effectG->getTag();

        // 2. compose(eff, compose)
        if ($isEff($fTag) and $isCompose($gTag)) {
            $effects = [
                $effectF,
                $effectG->getArg('effectF'),
                $effectG->getArg('effectG'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }

        // 3. compose(compose, eff)
        if ($isCompose($fTag) and $isEff($gTag)) {
            $effects = [
                $effectF->getArg('effectF'),
                $effectF->getArg('effectG'),
                $effectG,
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }
        // 4. compose(compose, compose)
        if ($isCompose($fTag) and $isCompose($gTag)) {
            $effects = [
                $effectF->getArg('effectF'),
                $effectF->getArg('effectG'),
                $effectG->getArg('effectF'),
                $effectG->getArg('effectG'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }

        // 5. compose(eff, composition)
        if ($isEff($fTag) and $isComposition($gTag)) {
            $effects = [
                $effectF,
                ...$effectG->getArg('effects'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }
        // 6. compose(composition, eff)
        if ($isComposition($fTag) and $isEff($gTag)) {
            $effects = [
                ...$effectF->getArg('effects'),
                $effectG,
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }
        // 7. compose(composition, composition)
        if ($isComposition($fTag) and $isComposition($gTag)) {
            $effects = [
                ...$effectF->getArg('effects'),
                ...$effectG->getArg('effects'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }

        // 8. compose(compose, composition)
        if ($isCompose($fTag) and $isComposition($gTag)) {
            $effects = [
                $effectF->getArg('effectF'),
                $effectF->getArg('effectG'),
                ...$effectG->getArg('effects'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }
        // 9. compose(composition, compose)
        if ($isComposition($fTag) and $isCompose($gTag)) {
            $effects = [
                ...$effectF->getArg('effects'),
                $effectG->getArg('effectF'),
                $effectG->getArg('effectG'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }

        // 1. compose(eff, eff) is the default
        return new self(Operation::create($composeTag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * @template _INPUTF
     * @template _INPUTG
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect< _INPUTF, _OUTPUTF, _ERRF> $effectF
     * @param KleisliEffect< _INPUTG, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect< Tuple<_INPUTF, _INPUTG>, Tuple<_OUTPUTF,  _OUTPUTG>, _ERRF|_ERRG>
     */
    public static function merge(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('merge');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    /**
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect<  _INPUT, _OUTPUTF, _ERRF> $effectF
     * @param KleisliEffect<  _INPUT, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect<  _INPUT,  Tuple<_OUTPUTF,  _OUTPUTG>, _ERRF|_ERRG>
     */
    public static function split(KleisliEffect $effectF, KleisliEffect $effectG): self
    {
        $tag = self::createTag('split');

        return new self(Operation::create($tag)->setArg('effectF', $effectF)->setArg('effectG', $effectG));
    }

    public function toString(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return [
            '_tag' => $this->operation->getTag(),
            'args' => $this->operation->getArgs(),
        ];
    }

    public function getTag(): string
    {
        return $this->operation->getTag();
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param callable(_INPUT):_OUTPUT $f
     *
     * @return KleisliEffect<_INPUT, _OUTPUT, mixed>
     */
    public static function liftPure(callable $f): KleisliEffect
    {
        $tag = self::createTag('lift-pure');

        return new self(Operation::create($tag)->setArg('f', $f));
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param callable(_INPUT):_OUTPUT $f
     *
     * @return KleisliEffect<_INPUT, _OUTPUT, mixed>
     */
    public static function liftImpure(callable $f): KleisliEffect
    {
        $tag = self::createTag('lift-impure');

        return new self(Operation::create($tag)->setArg('f', $f));
    }

    /**
     * f(A)=>M B
     * M == KleisliEffect.
     *
     * @template _OUTPUT
     * @template _ERR
     *
     * @param callable(OUTPUT):KleisliEffect<INPUT,_OUTPUT, _ERR> $f
     *
     * @return KleisliEffect<INPUT, _OUTPUT, _ERR>
     */
    public function flatmap(callable $f): KleisliEffect
    {
        $tag = self::createTag('flatmap');

        return new self(Operation::create($tag)->setArg('effect', $this)->setArg('f', $f));
    }

    /**
     * Combinators.
     */
    /**
     * @template _OUTPUTG
     * @template _ERRG
     *
     * @param KleisliEffect<OUTPUT, _OUTPUTG, _ERRG> $effectG
     *
     * @return KleisliEffect<INPUT, _OUTPUTG, _ERRG|ERR>
     */
    public function andThen(KleisliEffect $effectG): KleisliEffect
    {
        return self::compose($this, $effectG);
    }

    //     /**
    //  * @template _OUTPUTG
    //  * @template _ERRG
    //  *
    //  * @param callable ():KleisliEffect<OUTPUT, _OUTPUTG, _ERRG> $effectG
    //  *
    //  * @return KleisliEffect<INPUT, _OUTPUTG, _ERRG|ERR>
    //  */
    // public function andThenK(KleisliEffect $effectG): KleisliEffect
    // {
    //     return self::compose($this, $effectG);
    // }

    /**
     * @template _OUTPUTF
     * @template _ERRF
     * @template _ERRG
     *
     * @param KleisliEffect<OUTPUT, _OUTPUTF, _ERRF> $during
     * @param KleisliEffect<OUTPUT, null, _ERRG>     $release
     *
     * @return KleisliEffect<INPUT, Tuple<IOMonad<_OUTPUTF, _ERRF>,IOMonad<null,_ERRG>>,ERR>
     */
    public function bracket(KleisliEffect $during, KleisliEffect $release): KleisliEffect
    {
        $tag = self::createTag('bracket');

        return new self(
            Operation::create($tag)
                ->setArg('acquire', $this)
                ->setArg('during', $during)
                ->setArg('release', $release)
        );
    }

    /**
     * the control function will receive a continuation k.
     *
     * @template _INPUT
     * @template _ERR
     *
     * @param callable(KleisliEffect<_INPUT, _INPUT, _ERR>):KleisliEffect<_INPUT, _INPUT, _ERR> $f
     *
     * @return KleisliEffect<_INPUT, _INPUT, _ERR>
     */
    public static function control(callable $f): KleisliEffect
    {
        $tag = self::createTag('control');

        return new self(Operation::create($tag)->setArg('f', $f));
    }

    /**
     * Summary of run.
     *
     * @param INPUT $value
     *
     * @return KleisliEffect<null, OUTPUT, ERR>
     */
    public function stubInput($value): KleisliEffect
    {
        $tag = self::createTag('stub-input');

        return new self(Operation::create($tag)->setArg('effect', $this)->setArg('input', $value));
    }

    /**
     * @template _OUTPUTK
     * @template _ERRK
     *
     * @param KleisliEffect<OUTPUT, _OUTPUTK, _ERRK> $k
     *
     * @return KleisliEffect<INPUT, _OUTPUTK, _ERRK|ERR>
     */
    public function prompt(KleisliEffect $k): KleisliEffect
    {
        $composeTag = self::createTag('compose');
        $compositionTag = self::createTag('composition');
        $controlTag = self::createTag('control');

        $isCompose = fn (string $tag): bool => $composeTag == $tag;
        $isComposition = fn (string $tag): bool => $compositionTag == $tag;

        /**
         * @var callable(array<KleisliEffect>):Option<Tuple<int, KleisliEffect>>
         */
        $getControlEffect = function (array $effects) use ($controlTag): Option {
            foreach ($effects as $index => $effect) {
                if ($controlTag === $effect->getTag()) {
                    return Option::some(Tuple::create($index, $effect));
                }
            }

            return Option::none();
        };

        $getEffects = function (KleisliEffect $k) use ($isCompose, $isComposition): array {
            if ($isCompose($k->getTag())) {
                return [
                    $k->getArg('effectF'),
                    $k->getArg('effectG'),
                ];
            }
            if ($isComposition($k->getTag())) {
                return $k->getArg('effects');
            }

            return [$k];
        };

        $effects = call_user_func($getEffects, $k);

        $controlEffectOption = call_user_func($getControlEffect, $effects);

        return $controlEffectOption->match(
            function (Tuple $control) use ($effects, $compositionTag) {
                // evaluate stack with controlEffect
                $controlEffect = $control->snd();
                $controlF = $controlEffect->getArg('f');
                // hole is a value and not a arrow...
                $effectStackWithHole = function (KleisliEffect $hole) use ($control, $effects, $compositionTag) {
                    $controlIndex = $control->fst();
                    $initialEffects = array_slice($effects, 0, $controlIndex);
                    $afterEffects = ($controlIndex < count($effects)) ? array_slice($effects, $controlIndex + 1) : [];

                    $newEffectStack = [
                        ...$initialEffects,
                        $hole,
                        ...$afterEffects,
                    ];

                    return new self(Operation::create($compositionTag)->setArg('effects', $newEffectStack));
                };

                return $this->andThen(call_user_func($controlF, $effectStackWithHole));
            },
            fn () => $this->andThen($k)
        );
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _CONDERR
     * @template _THENERR
     * @template _ELSEERR
     *
     * @param KleisliEffect< _INPUT, bool, _CONDERR>    $cond
     * @param KleisliEffect< _INPUT, _OUTPUT, _THENERR> $then
     * @param KleisliEffect< _INPUT, _OUTPUT, _ELSEERR> $else
     *
     * @return KleisliEffect< _INPUT, _OUTPUT, _ELSEERR|_THENERR>
     */
    public static function ifThenElse(KleisliEffect $cond, KleisliEffect $then, KleisliEffect $else): KleisliEffect
    {
        $tag = self::createTag('if-then-else');

        return new self(
            Operation::create($tag)
                ->setArg('cond', $cond)
                ->setArg('then', $then)
                ->setArg('else', $else)
        );
    }

    /**
     * @template _INPUT
     * @template _OUTPUT
     * @template _THENERR
     * @template _ELSEERR
     *
     * @param KleisliEffect< _INPUT, _OUTPUT, _THENERR> $onLeft
     * @param KleisliEffect< _INPUT, _OUTPUT, _ELSEERR> $onRight
     *
     * @return KleisliEffect<Either<_INPUT,_INPUT>, _OUTPUT, _ELSEERR|_THENERR>
     */
    public static function choice(KleisliEffect $onLeft, KleisliEffect $onRight): KleisliEffect
    {
        $tag = self::createTag('choice');

        return new self(
            Operation::create($tag)
                ->setArg('onLeft', $onLeft)
                ->setArg('onRight', $onRight)
        );
    }

    private static function createTag(string $segment): string
    {
        return self::namespace.'.'.$segment;
    }
}
