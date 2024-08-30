<?php

declare(strict_types=1);

namespace Zodimo\DCF\Seq;

use Zodimo\DCF\Prompt\Prompt;

/**
 * @template CONTSEG
 * @template ANS
 * @template A
 *
 * @implements Seq<CONTSEG, ANS, A>
 */
class PushPSeq implements Seq
{
    private Prompt $prompt;
    private Seq $seq;

    private function __construct(Prompt $prompt, Seq $seq)
    {
        $this->prompt = $prompt;
        $this->seq = $seq;
    }

    public static function create(Prompt $prompt, Seq $seq): PushPSeq
    {
        return new PushPSeq($prompt, $seq);
    }

    public function getPrompt(): Prompt
    {
        return $this->prompt;
    }

    public function getSeq(): Seq
    {
        return $this->seq;
    }
}
