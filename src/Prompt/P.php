<?php

declare(strict_types=1);

namespace Zodimo\DCF\Prompt;

/**
 * P         the type of computations that might generate new prompts.
 *
 * @template ANS
 * @template A
 */
class P
{
    private $e;

    /**
     * @param callable(int):array{int, A} $e
     */
    public function __construct($e)
    {
        $this->e = $e;
    }

    /**
     * AKA return in haskell.
     *
     * @param A $e
     *
     * @return P<ANS,A>
     */
    public static function create($e): P
    {
        return new P(fn (int $s) => [$s, $e]);
    }

    /**
     * AKA bind in haskell.
     *
     * @template B
     *
     * @param callable(A):P<ANS, B> $e
     *
     * @return P<ANS, B>
     */
    public function flatmap($e): P
    {
        /**
         * instance Monad (P ans) where
         * (P e1) >>= e2 = P (\s1 -> case e1 s1 of
         *            (s2,v1) -> unP (e2 v1) s2).
         *
         * $this = P ans e1
         * $e = e2
         */
        $that = $this;

        return new P(function (int $s1) use ($that, $e) {
            [$s2, $v1] = call_user_func($that->e, $s1);

            $resultP = call_user_func($e, $v1);

            return call_user_func($resultP->e, $s2);
        });
    }

    /**
     * runP      performs the computation giving a pure answer.
     *
     * @return ANS
     */
    public function runP()
    {
        [$_,$result] = call_user_func($this->e, 0);

        return $result;
    }

    /**
     * Not so greate, cannot type generic return.
     *
     * @return P<mixed, Prompt<mixed, mixed>>
     */
    public static function newPrompt(): P
    {
        // newPromptName :: P ans (Prompt ans a)
        // newPromptName = P (\np -> (np+1, Prompt np))

        return new P(fn (int $np) => [$np + 1, new Prompt($np)]);
    }
}
