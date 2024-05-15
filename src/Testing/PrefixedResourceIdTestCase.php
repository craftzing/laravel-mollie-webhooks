<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing;

use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Generator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function call_user_func;
use function serialize;
use function unserialize;

abstract class PrefixedResourceIdTestCase extends TestCase
{
    private const EXPECTED_PREFIX = 're_';

    abstract protected static function resourceIdClass(): string;

    abstract protected static function expectedPrefix(): string;

    abstract protected static function expectedExceptionClass(): string;

    public static function invalidMollieResourceIds(): Generator
    {
        yield 'Value has no prefix' => [Str::random(8)];
        yield 'Value has an invalid prefix' => ['pa_' . Str::random(8)];
    }

    #[Test]
    #[DataProvider('invalidMollieResourceIds')]
    public function itCannotBeConstructedFromAnInvalidMollieResourceIdString(string $value): void
    {
        $this->expectExceptionObject(
            call_user_func([$this::expectedExceptionClass(), 'missingExpectedPrefix'], $value, $this::expectedPrefix())
        );

        $this::resourceIdClass()::fromString($value);
    }

    public static function validMollieResourceIds(): Generator
    {
        yield 'Short identifier' => [fn (self $test): string => $test::expectedPrefix() . Str::random(4)];
        yield 'Long identifier' => [fn (self $test): string => $test::expectedPrefix() . Str::random(8)];
    }

    #[Test]
    #[DataProvider('validMollieResourceIds')]
    public function itCanBeConstructedFromAValidMollieResourceIdString(callable $resolveValue): void
    {
        $value = $resolveValue($this);

        $resourceId = $this::resourceIdClass()::fromString($value);

        $this->assertInstanceOf(ResourceId::class, $resourceId);
    }

    #[Test]
    #[DataProvider('validMollieResourceIds')]
    public function itCanBeCastedToAString(callable $resolveValue): void
    {
        $value = $resolveValue($this);

        $resourceId = $this::resourceIdClass()::fromString($value);

        $this->assertSame($value, (string) $resourceId);
    }

    #[Test]
    #[DataProvider('validMollieResourceIds')]
    public function itCanReturnItsValue(callable $resolveValue): void
    {
        $value = $resolveValue($this);

        $resourceId = $this::resourceIdClass()::fromString($value);

        $this->assertSame($value, $resourceId->value());
    }

    #[Test]
    #[DataProvider('validMollieResourceIds')]
    public function itCanBeSerialized(callable $resolveValue): void
    {
        $value = $resolveValue($this);

        $resourceId = $this::resourceIdClass()::fromString($value);

        $serializedResourceId = serialize($resourceId);

        $this->assertEquals($resourceId, unserialize($serializedResourceId, [$this::resourceIdClass()]));
    }

    #[Test]
    public function itCanBeUsedAsAResourceId(): void
    {
        $resourceId = $this::resourceIdClass()::fromString($this::expectedPrefix() . Str::random(8));

        $this->assertInstanceOf(ResourceId::class, $resourceId);
    }
}
