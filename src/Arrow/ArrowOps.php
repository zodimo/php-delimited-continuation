<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

class ArrowOps
{
    /**
     * first :: a b c -> a (b,d) (c,d).
     * first = (*** id).
     *
     * A piping method first that takes an arrow between two types and
     * converts it into an arrow between tuples. The first elements in
     * the tuples represent the portion of the input and output that is altered,
     * while the second elements are a third type u describing an unaltered
     * portion that bypasses the computation.
     *
     * @template _M
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param Arrow<_M, _INPUT, _OUTPUT> $arrow
     *
     * @return Arrow<_M, Tuple<_INPUT, mixed>, Tuple<_OUTPUT, mixed>>
     */
    public static function first(Arrow $arrow): Arrow
    {
        $arrowClass = get_class($arrow);

        /**
         * @var callable(Tuple<_INPUT, mixed>):Tuple<_OUTPUT, mixed> $func
         */
        $func = function (Tuple $args) use ($arrow) {
            $input = $args->fst();
            $d = $args->snd();

            return Tuple::create($arrow->run($input), $d);
        };

        // @phpstan-ignore return.type
        return $arrowClass::arr($func);
    }

    /**
     * second :: a b c -> a (d,b) (d,c)
     * second = (id ***).
     *
     * @template _M
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param Arrow<_M, _INPUT, _OUTPUT> $arrow
     *
     * @return Arrow<_M, Tuple<mixed, _INPUT>, Tuple<mixed, _OUTPUT>>
     */
    public static function second(Arrow $arrow): Arrow
    {
        $arrowClass = get_class($arrow);

        /**
         * @var callable(Tuple<mixed, _INPUT>):Tuple<mixed, _OUTPUT> $func
         */
        $func = function (Tuple $args) use ($arrow) {
            $input = $args->snd();
            $d = $args->fst();

            return Tuple::create($d, $arrow->run($input));
        };

        // @phpstan-ignore return.type
        return $arrowClass::arr($func);
    }

    /**
     * ">>>".
     * A composition operator >>> that can attach a second arrow to a first
     * as long as the first function’s output and the second’s input have matching types.
     *
     * -- | Left-to-right composition
     * (>>>) :: Category cat => cat a b -> cat b c -> cat a c
     * f >>> g = g . f
     *
     * @template _M
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     *
     * @param Arrow<_M, _INPUT, _OUTPUTF>   $f
     * @param Arrow<_M, _OUTPUTF, _OUTPUTG> $g
     *
     * @return Arrow<_M, _INPUT, _OUTPUTG>
     */
    public static function compose(Arrow $f, Arrow $g)
    {
        $arrowClass = get_class($f);

        /**
         * @var callable(_INPUT):_OUTPUTG $func
         */
        $func = function ($input) use ($f, $g) {
            return $g->run($f->run($input));
        };

        return $arrowClass::arr($func);
    }

    /**
     * "***".
     * A merging operator *** that can take two arrows, possibly with different
     * input and output types, and fuse them into one arrow between two compound types.
     *
     * (***) :: a b c -> a b' c' -> a (b,b') (c,c')
     * f *** g = first f >>> arr swap >>> first g >>> arr swap
     *  where swap ~(x,y) = (y,x)
     *
     * @template _M
     * @template _INPUTF
     * @template _INPUTG
     * @template _OUTPUTF
     * @template _OUTPUTG
     *
     * @param Arrow<_M, _INPUTF, _OUTPUTF> $f
     * @param Arrow<_M, _INPUTG, _OUTPUTG> $g
     *
     * @return Arrow<_M, Tuple<_INPUTF, _INPUTG>, Tuple<_OUTPUTF,  _OUTPUTG>>
     */
    public static function merge(Arrow $f, Arrow $g): Arrow
    {
        $arrowClass = get_class($f);

        /**
         * @var callable(Tuple<_INPUTF, _INPUTG>):Tuple<_OUTPUTF,  _OUTPUTG> $func
         */
        $func = function (Tuple $args) use ($f, $g) {
            $fInput = $args->fst();
            $gInput = $args->snd();

            return Tuple::create($f->run($fInput), $g->run($gInput));
        };

        return $arrowClass::arr($func);
    }

    /**
     * "&&&".
     * (&&&) :: a b c -> a b c' -> a b (c,c')
     * f &&& g = arr (\b -> (b,b)) >>> f *** g.
     *
     * @template _M
     * @template _INPUT
     * @template _OUTPUTF
     * @template _OUTPUTG
     *
     * @param Arrow<_M, _INPUT, _OUTPUTF> $f
     * @param Arrow<_M, _INPUT, _OUTPUTG> $g
     *
     * @return Arrow<_M, _INPUT,  Tuple<_OUTPUTF,  _OUTPUTG>>
     */
    public static function split(Arrow $f, Arrow $g)
    {
        $arrowClass = get_class($f);

        /**
         * @var callable(_INPUT):Tuple<_OUTPUTF,  _OUTPUTG> $func
         */
        $func = function ($input) use ($f, $g) {
            return Tuple::create($f->run($input), $g->run($input));
        };

        return $arrowClass::arr($func);
    }

    /**
     * @template _M
     * @template _INPUT
     * @template _OUTPUT
     *
     * @param Arrow<_M, _INPUT, bool>    $cond
     * @param Arrow<_M, _INPUT, _OUTPUT> $then
     * @param Arrow<_M, _INPUT, _OUTPUT> $else
     *
     * @return Arrow<_M, _INPUT, _OUTPUT>
     */
    public static function ifThenElse(Arrow $cond, Arrow $then, Arrow $else): Arrow
    {
        $arrowClass = get_class($cond);

        /**
         * @var callable(_INPUT):_OUTPUT
         */
        $func = function ($input) use ($cond, $then, $else) {
            if ($cond->run($input)) {
                return $then->run($input);
            }

            return $else->run($input);
        };

        return $arrowClass::arr($func);
    }
}
