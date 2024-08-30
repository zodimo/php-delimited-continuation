<?php

declare(strict_types=1);

namespace Zodimo\DCF\Seq;

/**
 * @template CONTSEG
 * @template ANS
 * @template A
 *
 * @implements Seq<CONTSEG, ANS, ANS>
 */
class EmptySeq implements Seq
{
    private function __construct() {}

    public static function create()
    {
        return new EmptySeq();
    }
}
