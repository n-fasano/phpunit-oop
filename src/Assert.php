<?php

namespace Fasano\PhpUnitOop;

use Closure;
use PHPUnit\Framework\Assert as PhpUnitAssert;
use Throwable;

class Assert
{
    public static function equals(mixed $expected, mixed $actual, string $message = ''): void
    {
        PhpUnitAssert::assertEquals($expected, $actual, $message);
    }

    public static function true(mixed $condition, string $message = ''): void
    {
        PhpUnitAssert::assertTrue($condition, $message);
    }

    public static function false(mixed $condition, string $message = ''): void
    {
        PhpUnitAssert::assertFalse($condition, $message);
    }

    public static function throws(Throwable $expected, Closure $function): void
    {
        $didThrow = false;

        try {
            $function();    
        } catch (Throwable $thrown) {
            Assert::equals($expected::class, $thrown::class);
            Assert::equals($expected->getMessage(), $thrown->getMessage());
            $didThrow = true;
        }

        Assert::true($didThrow);
    }
}