<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Exception;

use function sprintf;

final class InvalidResourceId extends Exception
{
    public static function missingExpectedPrefix(string $value, string $prefix): self
    {
        return new self(
            "Value `$value` cannot be used as a Mollie resource identifier as it " .
            sprintf("is missing the expected `%s` prefix.", $prefix),
        );
    }
}
