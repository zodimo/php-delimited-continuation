<?php

declare(strict_types=1);

namespace Zodimo\DCF\Seq;

/**
 * @template CONTSEG
 * @template ANS
 * @template A
 *
 * @implements Seq<CONTSEG, ANS, A>
 */
class PushSegSeq implements Seq
{
    private $contseg;
    private Seq $seq;

    private function __construct($contseg, Seq $seq)
    {
        $this->contseg = $contseg;
        $this->seq = $seq;
    }

    public static function create($contseg, Seq $seq): PushSegSeq
    {
        return new PushSegSeq($contseg, $seq);
    }

    public function getContSeg()
    {
        return $this->contseg;
    }

    public function getSeq(): Seq
    {
        return $this->seq;
    }
}
