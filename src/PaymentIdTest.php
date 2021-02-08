<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidPaymentId;
use Generator;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

use function serialize;
use function unserialize;

final class PaymentIdTest extends TestCase
{
    private const EXPECTED_PREFIX = 'tr_';

    public function invalidMolliePaymentIds(): Generator
    {
        yield 'Value has no prefix' => [Str::random(8)];
        yield 'Value has an invalid prefix' => ['pa_' . Str::random(8)];
    }

    /**
     * @test
     * @dataProvider invalidMolliePaymentIds
     */
    public function itCannotBeConstructedFromAnInvalidMolliePaymentIdString(string $value): void
    {
        $this->expectExceptionObject(InvalidPaymentId::missingExpectedPrefix($value));

        PaymentId::fromString($value);
    }

    public function validMolliePaymentIds(): Generator
    {
        yield 'Short identifier' => [self::EXPECTED_PREFIX . Str::random(4)];
        yield 'Long identifier' => [self::EXPECTED_PREFIX . Str::random(8)];
    }

    /**
     * @test
     * @dataProvider validMolliePaymentIds
     */
    public function itCanBeConstructedFromAValidMolliePaymentIdString(string $value): void
    {
        $paymentId = PaymentId::fromString($value);

        $this->assertInstanceOf(PaymentId::class, $paymentId);
    }

    /**
     * @test
     * @dataProvider validMolliePaymentIds
     */
    public function itCanBeCastedToAString(string $value): void
    {
        $paymentId = PaymentId::fromString($value);

        $this->assertSame($value, (string) $paymentId);
    }

    /**
     * @test
     * @dataProvider validMolliePaymentIds
     */
    public function itCanReturnItsValue(string $value): void
    {
        $paymentId = PaymentId::fromString($value);

        $this->assertSame($value, $paymentId->value());
    }

    /**
     * @test
     * @dataProvider validMolliePaymentIds
     */
    public function itCanBeSerialized(string $value): void
    {
        $paymentId = PaymentId::fromString($value);

        $serializedPaymentId = serialize($paymentId);

        $this->assertEquals($paymentId, unserialize($serializedPaymentId));
    }
}
