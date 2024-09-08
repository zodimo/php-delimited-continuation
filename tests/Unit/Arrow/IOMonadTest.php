<?php

declare(strict_types=1);

namespace Zodimo\DCF\Tests\Unit\Arrow;

use PHPUnit\Framework\TestCase;
use Zodimo\BaseReturn\IOMonad;
use Zodimo\DCF\Tests\MockClosureTrait;

/**
 * @internal
 *
 * @coversNothing
 */
class IOMonadTest extends TestCase
{
    use MockClosureTrait;

    public function testCanCreate()
    {
        $m = IOMonad::pure(10);
        $this->assertInstanceOf(IOMonad::class, $m);
    }

    public function testPureIsSuccess()
    {
        $m = IOMonad::pure(10);
        $result = $m->isSuccess();
        $this->assertTrue($result);
    }

    public function testPureValue()
    {
        $onFailure = $this->createClosureMock();
        $onFailure->expects($this->never())->method('__invoke');
        $m = IOMonad::pure(10);
        $result = $m->unwrapSuccess($onFailure);
        $this->assertEquals(10, $result);
    }

    public function testFailIsFailure()
    {
        $m = IOMonad::fail(10);

        $this->assertTrue($m->isFailure());
    }

    public function testFailIsValue()
    {
        $onSuccess = $this->createClosureMock();
        $onSuccess->expects($this->never())->method('__invoke');

        $m = IOMonad::fail(10);

        $result = $m->unwrapFailure($onSuccess);
        $this->assertEquals(10, $result);
    }

    public function testFmapOnSuccess()
    {
        $onFailure = $this->createClosureMock();
        $onFailure->expects($this->never())->method('__invoke');

        $m = IOMonad::pure(10);
        $result = $m->fmap(fn ($x) => $x + 10);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(20, $result->unwrapSuccess($onFailure));
    }

    public function testFmapOnFailure()
    {
        $onSuccess = $this->createClosureMock();
        $onSuccess->expects($this->never())->method('__invoke');

        $m = IOMonad::fail(10);
        $result = $m->fmap(fn ($x) => $x + 10);
        $this->assertTrue($result->isFailure());
        $this->assertEquals(10, $result->unwrapFailure($onSuccess));
    }

    public function testFlatmapOnSuccess()
    {
        $onFailure = $this->createClosureMock();
        $onFailure->expects($this->never())->method('__invoke');

        $m = IOMonad::pure(10);
        $result = $m->flatmap(fn ($x) => IOMonad::pure($x + 10));
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(20, $result->unwrapSuccess($onFailure));
    }

    public function testFlatmapOnFailure()
    {
        $onSuccess = $this->createClosureMock();
        $onSuccess->expects($this->never())->method('__invoke');

        $m = IOMonad::fail(10);
        $result = $m->flatmap(fn ($x) => IOMonad::pure($x + 10));
        $this->assertTrue($result->isFailure());
        $this->assertEquals(10, $result->unwrapFailure($onSuccess));
    }

    public function testFlatmapOnSuccessWithFailure()
    {
        $onSuccess = $this->createClosureMock();
        $onSuccess->expects($this->never())->method('__invoke');

        $m = IOMonad::pure(10);
        $result = $m->flatmap(fn ($x) => IOMonad::fail(100));
        $this->assertTrue($result->isFailure());
        $this->assertEquals(100, $result->unwrapFailure($onSuccess));
    }

    public function testShouldNotComplareIOMonads()
    {
        $a = IOMonad::pure(10);
        $b = IOMonad::pure(11);
        $this->assertEquals($a, $b);
    }
}
