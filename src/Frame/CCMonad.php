<?php

declare(strict_types=1);

namespace Zodimo\DCF\Frame;

use Zodimo\DCF\Prompt\P;
use Zodimo\DCF\Prompt\Prompt;
use Zodimo\DCF\Seq\EmptySeq;
use Zodimo\DCF\Seq\PushPSeq;
use Zodimo\DCF\Seq\PushSegSeq;
use Zodimo\DCF\Seq\Seq;
use Zodimo\DCF\Seq\SeqService;
use Zodimo\DCF\Seq\SubSeqService;

/**
 * The interface includes the type constructor CC. The idea is that a term of type
 * (CC a) is a computation that may have control effects.
 *
 * @template ANS
 * @template A
 */
class CCMonad
{
    private $f;
    // instance Monad (CC ans) where
    // return v = CC (\k -> appk k v)
    // (CC e1) >>= e2 = CC (\k -> e1 (PushSeg (Frame e2) k))

    /**
     * newtype CC ans a = CC (Seq Frame ans a -> P ans ans).
     *
     * @param callable $f
     */
    private function __construct($f)
    {
        $this->f = $f;
    }

    /**
     * @param A $a
     *
     * @return CCMonad<ANS,  A>
     */
    public static function create($a): CCMonad
    {
        return new self(fn (Seq $k) => CCMonad::applyContinuation($k, $a));
    }

    /**
     * @template B
     *
     * @param callable(A):CCMonad<ANS, B> $e2
     *
     * @return CCMonad<ANS, B>
     */
    public function flatmap(callable $e2): CCMonad
    {
        // (CC e1) >>= e2 = CC (\k -> e1 (PushSeg (Frame e2) k))
        return new CCMonad(fn ($k) => $this->run(SeqService::pushSeg(Frame::create($e2))($k)));
    }

    public function run(Seq $seq): P
    {
        return call_user_func($this->f, $seq);
    }

    public static function newPrompt(): CCMonad
    {
        // newPrompt :: CC ans (Prompt ans a)
        // newPrompt = CC (\k -> do p <- newPromptName; appk k p)
        return new CCMonad(function ($k) {
            return P::newPrompt()->flatmap(fn ($p) => CCMonad::applyContinuation($k, $p));
        });
    }

    public static function pushPrompt(Prompt $prompt, CCMonad $cc): CCMonad
    {
        // -- Make the operation strict in k to avoid accumulation of
        // -- continuation segments
        // pushPrompt :: Prompt ans a -> CC ans a -> CC ans a
        // --pushPrompt p (CC e) = CC (\k -> e (PushP p k))
        // pushPrompt p (CC e) = CC (\k -> k `seq` e (PushP p k))

        return new CCMonad(fn ($k) => $cc->run(SeqService::pushP($prompt)($k)));
    }

    /**
     * @template B
     * @template CONTSEG
     *
     * @param Prompt<ANS, A>                                                                $p
     * @param callable(callable(Seq<CONTSEG, ANS, B>):Seq<CONTSEG, ANS, A>):CCMonad<ANS, B> $f
     *
     * @return CCMonad<ANS, A>
     */
    public static function withSubCont(Prompt $p, callable $f): CCMonad
    {
        // type SubSeq contseg ans a b = Seq contseg ans b -> Seq contseg ans a
        // withSubCont :: Prompt ans b -> (SubSeq Frame ans a b -> CC ans b) -> CC ans a
        // withSubCont p f =
        //     CC (\k -> let (subk, k') = splitSeq p k
        //           in unCC (f subk) k')

        return new CCMonad(function ($k) use ($p, $f) {
            [$subSeq, $_k] = SeqService::splitSeq($p, $k);

            /**
             * Pass the segment before the prompt to f.
             *
             * @var CCMonad<ANS, B> $m
             */
            $m = call_user_func($f, $subSeq);

            // run with the segment after the prompt
            return $m->run($_k);
        });
    }

    /**
     * @template B
     *
     * @param callable(Seq<Frame, ANS, B>):Seq<Frame, ANS, A> $subSeq
     * @param CCMonad<ANS, A>                                 $cc
     *
     * @return CCMonad<ANS,B>
     */
    public static function pushSubCont($subSeq, CCMonad $cc): CCMonad
    {
        // type SubSeq contseg ans a b = Seq contseg ans b -> Seq contseg ans a
        // pushSubCont :: SubSeq Frame ans a b -> CC ans a -> CC ans b
        // pushSubCont subk (CC e) = CC (\k -> e (pushSeq subk k))

        return new CCMonad(function ($k) use ($subSeq, $cc) {
            return $cc->run(SubSeqService::pushSeq($subSeq, $k));
        });
    }

    /**
     * @param A $a
     *
     * @return P<ANS, A>
     */
    public static function applyContinuation(Seq $seq, $a)
    {
        // -- Applies a continuation
        // appk :: Seq Frame ans a -> a -> P ans ans
        // appk EmptyS a 		= return a
        // appk (PushP _ k') a	= appk k' a
        // appk (PushSeg seg k') a = unCC (appseg seg a) k'

        if ($seq instanceof EmptySeq) {
            return P::create($a);
        }
        if ($seq instanceof PushPSeq) {
            return CCMonad::applyContinuation($seq->getSeq(), $a);
        }
        if ($seq instanceof PushSegSeq) {
            $frame = $seq->getContSeg();

            return CCMonad::applySegment($frame, $a)->run($seq->getSeq());
        }

        throw new \RuntimeException('Unknown Seq passed...:'.get_class($seq));
    }

    /**
     * @template B
     *
     * @param Frame<ANS, A, B> $frame
     * @param A                $a
     *
     * @return CCMonad<ANS,B>
     */
    public static function applySegment(Frame $frame, $a): CCMonad
    {
        // -- Applies a control segment
        // appseg :: Frame ans a b -> a -> CC ans b
        // appseg (Frame fr) a = fr a
        return $frame->run($a);
    }

    public static function runTerm(CCMonad $cc): P
    {
        // runTerm :: CC ans ans -> P ans ans
        // runTerm c = unCC c EmptyS
        return $cc->run(SeqService::emptyS());
    }

    /**
     * @param CCMonad<ANS, A> $cc
     *
     * @return ANS
     */
    public static function runCC(CCMonad $cc)
    {
        return CCMonad::runTerm($cc)->runP();
    }
}
