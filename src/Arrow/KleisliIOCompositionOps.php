<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

class KleisliIOCompositionOps
{
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
     * @template _EF
     * @template _EG
     *
     * @param KleisliIOComposition< _A, _B, _EF> $f
     * @param KleisliIOComposition<_B, _C, _EG>  $g
     *
     * @return KleisliIOComposition<_A, _C, _EF|_EG>
     */
    public static function compose(KleisliIOComposition $f, KleisliIOComposition $g): KleisliIOComposition
    {
        return $f->andThen($g);
    }
}
