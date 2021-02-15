<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Refunds;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidRefundId;
use Craftzing\Laravel\MollieWebhooks\PrefixedResourceId;
use Illuminate\Support\Str;

final class RefundId extends PrefixedResourceId
{
    public const PREFIX = 're_';

    protected function failWhenPrefixIsInvalid(string $value)
    {
        if (! Str::startsWith($value, self::PREFIX)) {
            throw InvalidRefundId::missingExpectedPrefix($value);
        }
    }
}
