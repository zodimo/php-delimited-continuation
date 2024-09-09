<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Effect\Effects\State;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\Effects\State\StateEffect;
use Zodimo\DCF\Effect\Effects\State\StateEffectHandler;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Effect\Router\BasicEffectRouter;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class StateEffectHandlerTest extends TestCase
{
    use MockClosureTrait;

    public function testCanCreate()
    {
        $handler = new StateEffectHandler([]);
        $this->assertInstanceOf(StateEffectHandler::class, $handler);
    }

    public function testCanHandleSet()
    {
        $effect = StateEffect::set(['name' => 'Joe']); // << set the state
        $handler = new StateEffectHandler([]);

        $router = BasicEffectRouter::create([
            StateEffect::class => $handler,
            KleisliEffect::class => new KleisliEffectHandler(),
        ]);

        $resultArrow = $router->perform($effect);
        $result = $resultArrow->run(10);

        $this->assertEquals(10, $result->unwrapSuccess($this->createClosureNotCalled()));
        $this->assertEquals(['name' => 'Joe'], $handler->getState());
    }

    public function testCanHandleGet()
    {
        $effect = StateEffect::get();
        $handler = new StateEffectHandler(['name' => 'Joe']); // << this sets the initial state

        $router = BasicEffectRouter::create([
            StateEffect::class => $handler,
            KleisliEffect::class => new KleisliEffectHandler(),
        ]);

        $resultArrow = $router->perform($effect);
        $result = $resultArrow->run(10);

        $expectedResult = Tuple::create(10, ['name' => 'Joe']);
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
