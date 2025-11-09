# PHPUnit OOP

**Object-oriented test case design for PHPUnit.**

This library doesn't add features. It adds **constraints that encourage clarity**.

The goal: bring the same design principles to your tests that you bring to your production code: encapsulation, polymorphism, and meaningful names. [Read more](docs/RATIONALE.md)

## Installation
```bash
composer require --dev fasano/phpunit-oop
```

## Core Concepts

### TestSuite

Your test classes now extend `TestSuite` (which extends PHPUnit's `TestCase`):
```php
use Fasano\PhpUnitOop\TestSuite;

class SomeFeatureTest extends TestSuite
{
    public static function cases(): array
    {
        return [
            'Case name' => new MyCaseObject(...),
            'Another case' => new AnotherCaseObject(...),
        ];
    }
}
```

### TestCase

Individual test scenarios extend `TestCase`:
```php
use Fasano\PhpUnitOop\TestCase;

class SuccessCase extends TestCase
{
    public function __construct(
        public readonly MyClass $object,
        public readonly MyInput $input,
        public readonly MyResult $result,
    ) {}

    public function verify(): void
    {
        Assert::equals($this->result, $this->object->process($this->input));
    }
}
```

The `verify()` method contains your assertions. It is automatically called by the test runner.

### Assert

A thin wrapper around PHPUnit's assertions to avoid naming collisions and unify the API:
```php
use Fasano\PhpUnitOop\Assert;

Assert::true($condition);
Assert::false($condition);
Assert::equals($expected, $actual);
Assert::throws($exception, fn() => $this->dangerousOperation());
```

## Requirements

- PHP 8.4 (other versions untested)
- PHPUnit 12.4 (other versions untested)

## License

MIT