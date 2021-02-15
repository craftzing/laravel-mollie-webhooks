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
    }
}
