<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class BasicRuntimeTest extends TestCase
{
    use MockClosureTrait;

    public function testCanCreate()
    {
        $runtime = BasicRuntime::create([]);
        $this->assertInstanceOf(BasicRuntime::class, $runtime);
    }

    public function testCanPerformEffect()
    {
        $runtime = BasicRuntime::create([KleisliEffect::class => new KleisliEffectHandler()]);

        $arrow = $runtime->perform(KleisliEffect::id());
        $result = $arrow->run(10);
        $expectedResult = 10;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
