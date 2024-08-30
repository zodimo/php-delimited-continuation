<?php

declare(strict_types=1);

namespace Zodimo\DCF\Prompt;

/**
 * @template ANS
 * @template A
 */
class Prompt
{
    private int $number;

    // -- A prompt (Prompt ans a) accepts values of type a; it was generated
    // -- in a region identified by a final answer of type ans; it can only
    // -- be used in a region with that final answer. The internal
    // -- representation of the prompt is just an integer
    public function __construct(int $number)
    {
        $this->number = $number;
    }

    /**
     * @param Prompt<ANS, A> $prompt
     */
    public function eq(Prompt $prompt): bool
    {
        return $this->number == $prompt->number;
    }
}
