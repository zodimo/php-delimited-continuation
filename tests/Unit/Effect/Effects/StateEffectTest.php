<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Effect\Effects;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Effect\Effects\StateEffect;

/**
 * @internal
 *
 * @coversNothing
 */
class StateEffectTest extends TestCase
{
    public function testSet()
    {
        $state = 10;
        $effect = StateEffect::set($state);

        $this->assertInstanceOf(StateEffect::class, $effect);
        $this->assertEquals('state-effect.set', $effect->getTag());
        $this->assertEquals($state, $effect->getArg('state'));
    }
}
