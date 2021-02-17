<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidPaymentId;
use Craftzing\Laravel\MollieWebhooks\HasIdPrefix;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Illuminate\Support\Str;

final class PaymentId implements ResourceId
{
    use HasIdPrefix;

    public const PREFIX = 'tr_';

    protected function failWhenPrefixIsInvalid(string $value): void
    {
        if (! Str::startsWith($value, self::PREFIX)) {
            throw InvalidPaymentId::missingExpectedPrefix($value);
        }
    }
}
