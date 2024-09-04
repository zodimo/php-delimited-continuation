<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\KleisliIO;
use Zodimo\DCF\Arrow\KleisliIOComposition;
use Zodimo\DCF\Arrow\KleisliIOCompositionOps;

/**
 * @internal
 *
 * @coversNothing
 */
class KleisliIOCompositionOpsTest extends TestCase
{
    public function testCompose()
    {
        $arrowFF = KleisliIO::liftPure(fn (int $x) => $x + 10);
        $arrowFG = KleisliIO::liftPure(fn (int $x) => $x * 10);

        $compoFF = KleisliIOComposition::intializeWith($arrowFF);
        $compoFG = KleisliIOComposition::intializeWith($arrowFG);

        $arrowComposed = KleisliIOCompositionOps::compose($compoFF, $compoFG);
        $result = $arrowComposed->run(10);
        $expectedResult = IOMonad::pure(200);
        $this->assertEquals($expectedResult, $result);
    }
}
