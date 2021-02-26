<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing;

use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Generator;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

use function call_user_func;
use function serialize;
use function unserialize;

abstract class PrefixedResourceIdTestCase extends TestCase
{
    private const EXPECTED_PREFIX = 're_';

    abstract protected function resourceIdClass(): string;

    abstract protected function expectedPrefix(): string;

    abstract protected function expectedExceptionClass(): string;

    public function invalidMollieResourceIds(): Generator
    {
        yield 'Value has no prefix' => [Str::random(8)];
        yield 'Value has an invalid prefix' => ['pa_' . Str::random(8)];
    }

    /**
     * @test
     * @dataProvider invalidMollieResourceIds
     */
    public function itCannotBeConstructedFromAnInvalidMollieResourceIdString(string $value): void
    {
        $this->expectExceptionObject(
            call_user_func([$this->expectedExceptionClass(), 'missingExpectedPrefix'], $value, $this->expectedPrefix())
        );

        $this->resourceIdClass()::fromString($value);
    }

    public function validMollieResourceIds(): Generator
    {
        yield 'Short identifier' => [$this->expectedPrefix() . Str::random(4)];
        yield 'Long identifier' => [$this->expectedPrefix() . Str::random(8)];
    }

    /**
     * @test
     * @dataProvider validMollieResourceIds
     */
    public function itCanBeConstructedFromAValidMollieResourceIdString(string $value): void
    {
        $resourceId = $this->resourceIdClass()::fromString($value);

        $this->assertInstanceOf(ResourceId::class, $resourceId);
    }

    /**
     * @test
     * @dataProvider validMollieResourceIds
     */
    public function itCanBeCastedToAString(string $value): void
    {
        $resourceId = $this->resourceIdClass()::fromString($value);

        $this->assertSame($value, (string) $resourceId);
    }

    /**
     * @test
     * @dataProvider validMollieResourceIds
     */
    public function itCanReturnItsValue(string $value): void
    {
        $resourceId = $this->resourceIdClass()::fromString($value);

        $this->assertSame($value, $resourceId->value());
    }

    /**
     * @test
     * @dataProvider validMollieResourceIds
     */
    public function itCanBeSerialized(string $value): void
    {
        $resourceId = $this->resourceIdClass()::fromString($value);

        $serializedResourceId = serialize($resourceId);

        $this->assertEquals($resourceId, unserialize($serializedResourceId));
    }

    /**
     * @test
     */
    public function itCanBeUsedAsAResourceId(): void
    {
        $resourceId = $this->resourceIdClass()::fromString($this->expectedPrefix() . Str::random(8));

        $this->assertInstanceOf(ResourceId::class, $resourceId);
    }
}
