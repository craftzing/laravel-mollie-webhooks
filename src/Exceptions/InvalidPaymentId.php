<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Exception;

use function sprintf;

final class InvalidPaymentId extends Exception
{
    public static function missingExpectedPrefix(string $value): self
    {
        return new self(
            "Value `$value` cannot be used as a Mollie payment identifier as it " .
            sprintf("is missing the expected `%s` prefix.", PaymentId::PREFIX),
        );
    }
}
