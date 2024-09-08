<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliCompositionEffect;
use Zodimo\DCF\Effect\KleisliCompositionEffectHandler;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliCompositionEffectHandlerTest extends TestCase
{
    use MockClosureTrait;

    public function testCanHandleCompose()
    {
        $funcF = fn (int $x) => IOMonad::pure($x + 10);
        $funcG = fn (int $x) => IOMonad::pure($x * 10);

        $effectF = KleisliCompositionEffect::arr($funcF);
        $effectG = KleisliCompositionEffect::arr($funcG);

        $handler = new KleisliCompositionEffectHandler();
        $runtime = BasicRuntime::create([KleisliCompositionEffect::class => $handler]);

        $arrowComposed = $handler->handle(KleisliCompositionEffect::compose($effectF, $effectG), $runtime);

        $result = $arrowComposed->run(10);
        $expectedResult = 200;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
