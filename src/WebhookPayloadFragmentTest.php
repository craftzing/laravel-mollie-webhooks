<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Generator;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function count;

final class WebhookPayloadFragmentTest extends TestCase
{
    public function keys(): Generator
    {
        yield 'No keys' => [''];
        yield 'Single key' => ['foo'];
        yield 'Multiple keys' => ['foo', 'bar', 'baz'];
    }

    /**
     * @test
     * @dataProvider keys
     */
    public function itCanBeConstructedFromKeys(string ...$keys): void
    {
        $keys = array_filter($keys);

        $payloadFragment = WebhookPayloadFragment::fromKeys(...$keys);

        $this->assertCount(count($keys), $payloadFragment->keys());
        $this->assertSame($keys, $payloadFragment->keys());
        $this->assertEmpty($payloadFragment->values());
    }

    public function values(): Generator
    {
        yield 'No values' => [
            [],
        ];

        yield 'Single value' => [
            ['foo'],
        ];

        yield 'List of values' => [
            ['foo', 'bar', 'baz'],
        ];

        yield 'Associative list of values' => [
            [
                'foo' => 'bar',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider values
     */
    public function itCanBeConstructedFromValues(array $values): void
    {
        $values = array_filter($values);

        $payloadFragment = WebhookPayloadFragment::fromValues($values);

        $this->assertCount(count($values), $payloadFragment->values());
        $this->assertSame($values, $payloadFragment->values());
        $this->assertEmpty($payloadFragment->keys());
    }
}
