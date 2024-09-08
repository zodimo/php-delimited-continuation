<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Arrow\KleisliIOComposition;
use Zodimo\DCF\Arrow\KleisliIOCompositionOps;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliIOCompositionOpsTest extends TestCase
{
    use MockClosureTrait;

    public function testCompose()
    {
        $arrowFF = KleisliIO::liftPure(fn (int $x) => $x + 10);
        $arrowFG = KleisliIO::liftPure(fn (int $x) => $x * 10);

        $compoFF = KleisliIOComposition::intializeWith($arrowFF);
        $compoFG = KleisliIOComposition::intializeWith($arrowFG);

        $arrowComposed = KleisliIOCompositionOps::compose($compoFF, $compoFG);
        $result = $arrowComposed->run(10);
        $expectedResult = 200;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }

    public function testAppendArrow()
    {
        $arrowFF = KleisliIO::liftPure(fn (int $x) => $x + 10);
        $arrowFG = KleisliIO::liftPure(fn (int $x) => $x * 10);

        $compoFF = KleisliIOComposition::intializeWith($arrowFF);

        $arrowComposed = KleisliIOCompositionOps::appendArrow($compoFF, $arrowFG);
        $result = $arrowComposed->run(10);
        $expectedResult = 200;
        $this->assertEquals($expectedResult, $result->unwrapSuccess($this->createClosureNotCalled()));
    }
}
