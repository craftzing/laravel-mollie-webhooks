<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Exception;

use function sprintf;

final class InvalidRefundId extends Exception
{
    public static function missingExpectedPrefix(string $value): self
    {
        return new self(
            "Value `$value` cannot be used as a Mollie refund identifier as it " .
            sprintf("is missing the expected `%s` prefix.", RefundId::PREFIX),
        );
    }
}
