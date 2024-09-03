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
     * @template _A
     * @template _B
     * @template _C
     *
     * @param Arrow<_A, _B, _C> $arrow
     *
     * @return Arrow<_A, Tuple<_B, mixed>, Tuple<_C, mixed>>
     */
    public static function first(Arrow $arrow): Arrow
    {
        $arrowClass = get_class($arrow);

        /**
         * @var callable(Tuple<_B, mixed>):Tuple<_C, mixed> $func
         */
        $func = function (Tuple $args) use ($arrow) {
            $input = $args->fst();
            $d = $args->snd();

            return Tuple::create($arrow->run($input), $d);
        };

        return $arrowClass::arr($func);
    }

    /**
     * second :: a b c -> a (d,b) (d,c)
     * second = (id ***).
     *
     * @template _A
     * @template _B
     * @template _C
     *
     * @param Arrow<_A, _B, _C> $arrow
     *
     * @return Arrow<_A, Tuple<mixed, _B>, Tuple<mixed, _C>>
     */
    public static function second(Arrow $arrow): Arrow
    {
        $arrowClass = get_class($arrow);

        /**
         * @var callable(Tuple<mixed, _B>):Tuple<mixed, _C> $func
         */
        $func = function (Tuple $args) use ($arrow) {
            $input = $args->snd();
            $d = $args->fst();

            return Tuple::create($d, $arrow->run($input));
        };

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
     * @template _A
     * @template _B
     * @template _C
     * @template _D
     *
     * @param Arrow<_A, _B, _C> $f
     * @param Arrow<_A, _C, _D> $g
     *
     * @return Arrow<_A, _B, _D>
     */
    public static function compose(Arrow $f, Arrow $g)
    {
        $arrowClass = get_class($f);

        /**
         * @var callable(_B):_D $func
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
     * @template _A
     * @template _B
     * @template __B
     * @template _C
     * @template __C
     *
     * @param Arrow<_A, _B, _C>   $f
     * @param Arrow<_A, __B, __C> $g
     *
     * @return Arrow<_A, Tuple<_B, __B>, Tuple<_C,  __C>>
     */
    public static function merge(Arrow $f, Arrow $g): Arrow
    {
        $arrowClass = get_class($f);

        /**
         * @var callable(Tuple<_B, __B>):Tuple<_C,  __C> $func
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
     * @template _A
     * @template _B
     * @template _C
     * @template __C
     *
     * @param Arrow<_A, _B, _C>  $f
     * @param Arrow<_A, _B, __C> $g
     *
     * @return Arrow<_A, _B,  Tuple<_C,  __C>>
     */
    public static function split(Arrow $f, Arrow $g)
    {
        $arrowClass = get_class($f);

        /**
         * @var callable(_B):Tuple<_C,  __C> $func
         */
        $func = function ($input) use ($f, $g) {
            return Tuple::create($f->run($input), $g->run($input));
        };

        return $arrowClass::arr($func);
    }
}
