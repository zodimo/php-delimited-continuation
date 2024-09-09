<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect\Router;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Effect\Router\BasicEffectRouter;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class BasicEffectRouterTest extends TestCase
{
    use MockClosureTrait;

    public function testCanCreate()
    {
        $runtime = BasicEffectRouter::create([]);
        $this->assertInstanceOf(BasicEffectRouter::class, $runtime);
    }

    public function testCanPerformEffect()
    {
        $runtime = BasicEffectRouter::create([KleisliEffect::class => new KleisliEffectHandler()]);

        $arrow = $runtime->perform(KleisliEffect::id());
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
