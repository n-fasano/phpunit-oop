# The Problem

Traditional PHPUnit tests suffer from a terminology mismatch:
```php
class DivisionTest extends TestCase // This is actually a test suite
{
    public function testIntegerDivision(): void // This is a test case
    {
        self::assertEquals(3, Math::div(6, 2));
    }

    public function testFloatDivision(): void // This is a test case
    {
        self::assertEquals(3.25, Math::div(6.5, 2));
    }

    public function testDivisionByZero(): void // This is a test case
    {
        $this->expectException(new DivisionByZeroError);

        Math::div(6, 0);
    }
}
```

A common reflex is to group test cases using data providers:
```php
class DivisionTest extends TestCase // This is still a test suite
{
    public function testDivisionByZeroError(): void // This is still a test case
    {
        $this->expectException(new DivisionByZeroError);

        Math::div(6, 0);
    }

    #[DataProvider('cases')]
    public function testDivision( // Now this is a test suite too
        int|float $dividend,
        int|float $divisor,
        int|float $result,
    ): void {
        self::assertEquals($result, Math::div($dividend, $divisor));
    }

    public function cases(): iterable
    {
        yield 'Integer division' => [6, 2, 3];
        yield 'Float division' => [6.5, 2, 3.25];
    }
}
```

Our error case is still separate, so let's parameterize:
```php
class DivisionTest extends TestCase
{
    #[DataProvider('cases')]
    public function test(
        int|float $dividend,
        int|float $divisor,
        int|float|null $result,
        ?Throwable $exception = null,
    ): void {
        if (null !== $exception) {
            $this->expectException($exception);
        }

        $result = Math::div($a, $b);

        if (null !== $expectedResult) {
            self::assertSame($expectedResult, $result);
        }
    }

    public function cases(): iterable
    {
        yield 'Regular division' => [6, 2, 3];
        yield 'Float division' => [6.5, 2, 3.25];
        yield 'Division error' => [1, 0, null, new DivisionByZeroError];
    }
}
```

We've managed to unify our test runner and cases! Buuut... Our test is now clunky, inelegant, and not type safe.

Problems with classic approaches:
- **Semantic confusion**: A "TestCase" class actually contains multiple test cases
- **Branching logic**: Different expected outcomes require conditionals in test code

## The Solution

Bring proper object-oriented design to your tests:
```php
use Fasano\PhpUnitOopCases\Assert;
use Fasano\PhpUnitOopCases\TestCase;
use Fasano\PhpUnitOopCases\TestSuite;

class DivisionTest extends TestSuite
{
    public static function cases(): iterable
    {
        yield 'Regular division' => new LegalDivisionCase(6, 2, 3);
        yield 'Float division' => new LegalDivisionCase(6.5, 2, 3.25);
        yield 'Division error' => new IllegalDivisionCase(1, 0, new DivisionByZeroError);
    }
}

class LegalDivisionCase extends TestCase
{
    public function __construct(
        protected readonly int|float $a,
        protected readonly int|float $b,
        protected readonly int|float $result,
    ) {}

    public function verify(): void
    {
        Assert::equals($this->result, Math::div($this->a, $this->b));
    }
}

class IllegalDivisionCase extends TestCase
{
    public function __construct(
        protected readonly int|float $a,
        protected readonly int|float $b,
        protected readonly Throwable $error,
    ) {}

    public function verify(): void
    {
        Assert::throws($this->error, fn() => Math::div($this->a, $this->b));
    }
}
```
