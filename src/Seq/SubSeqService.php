<?php

declare(strict_types=1);

namespace Zodimo\DCF\Seq;

class SubSeqService
{
    /**
     * @return callable(T=mixed):T
     */
    public static function emptySubSeq(): callable
    {
        // emptySubSeq :: SubSeq contseg ans a a
        // emptySubSeq = id
        return fn ($x) => $x;
    }

    /**
     * @template CONTSEG
     * @template ANS
     * @template A
     * @template B
     * @template C
     *
     * @param callable(Seq<CONTSEG, ANS, B>):Seq<CONTSEG, ANS, A> $subseqF
     * @param callable(Seq<CONTSEG, ANS, C>):Seq<CONTSEG, ANS, B> $subseqG
     *
     * @return callable(Seq<CONTSEG, ANS, C>):Seq<CONTSEG, ANS, A>
     */
    public static function appendSubSeq(callable $subseqF, callable $subseqG): callable
    {
        // type SubSeq contseg ans a b = Seq contseg ans b -> Seq contseg ans a
        // type SubSeq contseg ans b c = Seq contseg ans c -> Seq contseg ans b

        // appendSubSeq :: SubSeq contseg ans a b -> SubSeq contseg ans b c -> SubSeq contseg ans a c
        // appendSubSeq = (.)

        // f a b :: b -> a
        // g b c :: c -> b
        //
        // (.) f g
        // f .g
        // f after g
        // b->a after c->b
        // \c-> f(g(c))

        return function (Seq $c) use ($subseqF, $subseqG) {
            /**
             * @var Seq<CONTSEG, ANS, B> $b
             */
            $b = call_user_func($subseqG, $c);

            /**
             * @return Seq<CONTSEG, ANS, A>
             */
            return call_user_func($subseqF, $b);
        };
    }

    /**
     * @template CONTSEG
     * @template ANS
     * @template A
     * @template B
     *
     * @param Seq<CONTSEG, ANS, B>                                $seq
     * @param callable(Seq<CONTSEG, ANS, B>):Seq<CONTSEG, ANS, A> $subseq
     *
     * @return Seq<CONTSEG, ANS,A>
     */
    public static function pushSeq($subseq, Seq $seq): Seq
    {
        // type SubSeq contseg ans a b = Seq contseg ans b -> Seq contseg ans a

        // pushSeq :: SubSeq contseg ans a b -> Seq contseg ans b -> Seq contseg ans a
        // -- Push a SubSeq onto the front of a Seq, returning a Seq
        // pushSeq = ($)
        //  ($) f g
        // f $ g
        // f (g)

        return call_user_func($subseq, $seq);
    }
}
