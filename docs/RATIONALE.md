# The Problem

Traditional PHPUnit tests suffer from a terminology mismatch:
```php
class PeriodsOverlapTest extends TestCase // This is actually a test suite
{
    public function testNoOverlap(): void // This is a test case
    {
        $periodA = Period::create(...);
        $periodB = Period::create(...);

        self::assertFalse($periodA->overlapsWith($periodB));
    }

    public function testPartialOverlap(): void // This is a test case
    {
        $periodA = Period::create(...);
        $periodB = Period::create(...);

        self::assertTrue($periodA->overlapsWith($periodB));
    }

    public function testPartialOverlap(): void // This is a test case
    {
        $periodA = Period::create(...);
        $periodB = Period::create(...);

        self::assertTrue($periodA->overlapsWith($periodB));
    }
}
```

This is alleviated by data providers, but not fully:
```php
class PeriodsOverlapTest extends TestCase // This is still a test suite
{
    public function testNoOverlap(): void // This is still a test case
    {
        $periodA = Period::create(...);
        $periodB = Period::create(...);

        self::assertFalse($periodA->overlapsWith($periodB));
    }

    #[DataProvider('overlapCases')]
    public function testOverlap( // Now this is a test suite too
        Period $periodA,
        Period $periodB,
    ): void {
        self::assertTrue($periodA->overlapsWith($periodB));
    }

    public function overlapCases(): iterable
    {
        yield 'Partial overlap' => [
            Period::create(...),
            Period::create(...),
        ];

        yield 'Complete overlap' => [
            Period::create(...),
            Period::create(...),
        ];
    }
}
```

In that last example, you may have thought: "But testNoOverlap could also be abstracted away with the other cases by parameterizing the expected result!". And you'd be right.

```php
class PeriodsOverlapTest extends TestCase // This is still a test suite
{
    #[DataProvider('cases')]
    public function test( // This is now just a test runner
        Period $periodA,
        Period $periodB,
        bool $overlaps,
    ): void {
        self::assertSame($overlaps, $periodA->overlapsWith($periodB));
    }

    public function cases(): iterable // And these are our cases
    {
        yield 'No overlap' => [
            Period::create(...),
            Period::create(...),
            false,
        ];

        yield 'Partial overlap' => [
            Period::create(...),
            Period::create(...),
            true,
        ];

        yield 'Complete overlap' => [
            Period::create(...),
            Period::create(...),
            true,
        ];
    }
}
```

However parameterization is not a silver bullet:
```php
class DivisionTest extends TestCase
{
    #[DataProvider('cases')]
    public function test(
        int|float $a,
        int|float $b,
        int|float|null $expectedResult,
        ?Throwable $exception = null,
    ): void {
        if (null !== $exception) {
            $this->expectException($exception);
        }

        $result = Math::div($a, $b);

        if (null !== $expectedResult) {
            self::assertSame($expectedResult, $result);
        }

        /**
         * This is clunky, inelegant, and not type safe.
         */
    }

    public function cases(): iterable
    {
        yield 'Regular division' => [6, 2, 3];
        yield 'Float division' => [6.5, 2, 3.25];
        yield 'Division error' => [1, 0, null, new DivisionByZeroError];
    }
}
```

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
        protected readonly int $a,
        protected readonly int $b,
        protected readonly int $result,
    ) {}

    public function verify(): void
    {
        Assert::equals($this->result, Math::div($this->a, $this->b));
    }
}

class IllegalDivisionCase extends TestCase
{
    public function __construct(
        protected readonly int $a,
        protected readonly int $b,
        protected readonly Throwable $error,
    ) {}

    public function verify(): void
    {
        Assert::throws($this->error, fn() => Math::div($this->a, $this->b));
    }
}
```

Other examples:
```php
class IsOverlapping extends AbstractOverlapCase
{
    public function verify(): void
    {
        Assert::true($this->periodA->isOverlapping($this->periodB));
        Assert::true($this->periodB->isOverlapping($this->periodA));
    }
}

class IsNotOverlapping extends AbstractOverlapCase
{
    public function verify(): void
    {
        Assert::false($this->periodA->isOverlapping($this->periodB));
        Assert::false($this->periodB->isOverlapping($this->periodA));
    }
}

class IsBefore extends AbstractBeforeAfterCase
{
    public function verify(Assert $assert): void
    {
        $assert->true($this->period->isBefore($this->date));
        $assert->false($this->period->isAfter($this->date));
    }
}

class IsNotBefore extends AbstractBeforeAfterCase
{
    public function verify(Assert $assert): void
    {
        $assert->false($this->period->isBefore($this->date));
    }
}
```

Notice the asymmetry between `IsBefore` and `IsNotBefore`? Or the inverse testing in overlap cases? All of these are logical variations that are invisibilized by parameterization.