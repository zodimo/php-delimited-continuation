<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Integration\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\DCF\Arrow\ArrowF;

/**
 * @internal
 *
 * @coversNothing
 */
class ArrowLawsFTest extends TestCase
{
    // -- | The basic arrow class.
    // --
    // -- Instances should satisfy the following laws:
    // --
    // --  * @'arr' id = 'id'@
    // --
    // --  * @'arr' (f >>> g) = 'arr' f >>> 'arr' g@
    // --
    // --  * @'first' ('arr' f) = 'arr' ('first' f)@
    // --
    // --  * @'first' (f >>> g) = 'first' f >>> 'first' g@
    // --
    // --  * @'first' f >>> 'arr' 'fst' = 'arr' 'fst' >>> f@
    // --
    // --  * @'first' f >>> 'arr' ('id' *** g) = 'arr' ('id' *** g) >>> 'first' f@
    // --
    // --  * @'first' ('first' f) >>> 'arr' assoc = 'arr' assoc >>> 'first' f@
    // --
    // -- where
    // --
    // -- > assoc ((a,b),c) = (a,(b,c))
    // --
    // -- The other combinators have sensible default definitions,
    // -- which may be overridden for efficiency.

    public function testArrId()
    {
        $idArrowF = ArrowF::arr(fn ($x) => ArrowF::id()->run($x));
        $result = $idArrowF->run(100);
        $this->assertEquals(100, $result);
    }
}
