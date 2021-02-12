<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidPaymentId;
use Illuminate\Support\Str;

final class PaymentId implements ResourceId
{
    public const PREFIX = 'tr_';

    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    private static function isValid(string $value): bool
    {
        return Str::startsWith($value, self::PREFIX);
    }

    public static function fromString(string $value): self
    {
        if (! self::isValid($value)) {
            throw InvalidPaymentId::missingExpectedPrefix($value);
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
