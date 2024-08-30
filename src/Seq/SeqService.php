<?php

declare(strict_types=1);

namespace Zodimo\DCF\Seq;

use Zodimo\DCF\Prompt\Prompt;

/**
 * @template CONTSEG
 * @template ANS
 * @template A
 */
class SeqService
{
    /**
     * @return array{\Zodimo\DCF\Frame\Frame, Seq}
     *
     * @throws \RuntimeException
     */
    public static function splitSeq(Prompt $prompt, Seq $seq): array
    {
        if ($seq instanceof EmptySeq) {
            throw new \RuntimeException('Prompt was not found on the stack');
        }

        if ($seq instanceof PushPSeq) {
            // splitSeq p (PushP p' sk) = case eqPrompt p' p of
            //                                  EQUAL     -> (emptySubSeq, sk)
            //                                  NOT_EQUAL -> case splitSeq p sk of
            //                                                      (subk,sk') -> (appendSubSeq (PushP p') subk, sk')
            if ($seq->getPrompt()->eq($prompt)) {
                return [SubSeqService::emptySubSeq(), $seq->getSeq()];
            }
            [$_subSeq, $_seq] = SeqService::splitSeq($prompt, $seq->getSeq());

            return [SubSeqService::appendSubSeq(SeqService::pushP($seq->getPrompt()), $_subSeq), $_seq];
        }

        if ($seq instanceof PushSegSeq) {
            // splitSeq :: Prompt ans b -> Seq contseg ans a -> (SubSeq contseg ans a b, Seq contseg ans b)
            // splitSeq p (PushSeg seg sk) = case splitSeq p sk of
            //                                  (subk,sk') -> (appendSubSeq (PushSeg seg) subk, sk')

            [$_subSeq, $_seq] = SeqService::splitSeq($prompt, $seq->getSeq());
            $contSeq = $seq->getContSeg();

            return [SubSeqService::appendSubSeq(SeqService::pushSeg($contSeq), $_subSeq), $_seq];
        }

        throw new \RuntimeException('Unknown Seq passed...:'.get_class($seq));
    }

    /**
     * @return Seq<CONTSEG, ANS, ANS>
     */
    public static function emptyS(): Seq
    {
        return EmptySeq::create();
    }

    /**
     * @param Prompt<ANS, A> $prompt
     *
     * @return callable(Seq<CONTSEG, ANS, A>):Seq<CONTSEG, ANS, A>
     */
    public static function pushP(Prompt $prompt)
    {
        // $arg=fn(Seq $seq)=> $
        return fn (Seq $seq) => PushPSeq::create($prompt, $seq);
    }

    /**
     * @param mixed $seg
     *
     * @return callable(Seq<CONTSEG, ANS, A>):Seq<CONTSEG, ANS, A>
     */
    public static function pushSeg($seg)
    {
        return fn (Seq $seq) => PushSegSeq::create($seg, $seq);
    }
}
