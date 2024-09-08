<?php

declare(strict_types=1);

namespace Zodimo\DCF\Effect;

use Zodimo\BaseReturn\Either;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\BaseReturn\Tuple;

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

    public static function create(Operation $operation): self
    {
        return new self($operation);
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
     * @template _INPUT
     *
     * @return KleisliEffect<_INPUT, _INPUT, mixed>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
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
        $compositionTag = self::createTag('composition');

        $isEff = fn (string $tag): bool => $compositionTag !== $tag;
        $isComposition = fn (string $tag): bool => $compositionTag == $tag;
        $fTag = $effectF->getTag();
        $gTag = $effectG->getTag();

        // 2. compose(eff, composition)
        if ($isEff($fTag) and $isComposition($gTag)) {
            $effects = [
                $effectF,
                ...$effectG->getArg('effects'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }

        // 3. compose(composition, eff)
        if ($isComposition($fTag) and $isEff($gTag)) {
            $effects = [
                ...$effectF->getArg('effects'),
                $effectG,
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }
        // 4. compose(composition, composition)
        if ($isComposition($fTag) and $isComposition($gTag)) {
            $effects = [
                ...$effectF->getArg('effects'),
                ...$effectG->getArg('effects'),
            ];

            return new self(Operation::create($compositionTag)->setArg('effects', $effects));
        }

        // 1. compose(eff, eff) is the default
        $effects = [
            $effectF,
            $effectG,
        ];

        return new self(Operation::create($compositionTag)->setArg('effects', $effects));
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
     * the control function will receive a continuation k.
     *
     * @template _INPUT
     * @template _ERR
     *
     * @param callable(KleisliEffect<_INPUT, _INPUT, _ERR>):KleisliEffect<_INPUT, _INPUT, _ERR> $f
     *
     * @return KleisliEffect<_INPUT, _INPUT, _ERR>
     */
    public static function shift(callable $f): KleisliEffect
    {
        $tag = self::createTag('shift');

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
     * @param KleisliEffect<OUTPUT, _OUTPUTK, _ERRK> $effect
     *
     * @return KleisliEffect<INPUT, _OUTPUTK, _ERRK|ERR>
     */
    public static function reset(KleisliEffect $effect): KleisliEffect
    {
        $tag = self::createTag('reset');

        return new self(Operation::create($tag)->setArg('effect', $effect));
    }

    /**
     * @template _OUTPUTK
     * @template _ERRK
     *
     * @param KleisliEffect<OUTPUT, _OUTPUTK, _ERRK> $effect
     *
     * @return KleisliEffect<INPUT, _OUTPUTK, _ERRK|ERR>
     */
    public static function prompt(KleisliEffect $effect): KleisliEffect
    {
        $tag = self::createTag('prompt');

        return new self(Operation::create($tag)->setArg('effect', $effect));
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

    public static function createTag(string $segment): string
    {
        return self::namespace.'.'.$segment;
    }
}
