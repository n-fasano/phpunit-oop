<?php

namespace Fasano\PhpUnitOop;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as PhpUnitTestSuite;

abstract class TestSuite extends PhpUnitTestSuite
{
    #[DataProvider('provider')]
    public function test(TestCase $case): void
    {
        $case->verify();
    }

    public static function provider(): iterable
    {
        foreach (static::cases() as $id => $case) {
            yield $id => [$case];
        }
    }

    /** @return iterable<int|string, TestCase> */
    abstract public static function cases(): iterable;
}