<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Exception;

final class InvalidResourceId extends Exception
{
    public static function missingExpectedPrefix(string $value, string $prefix): self
    {
        return new self(
            "Value `$value` cannot be used as a Mollie resource identifier as it "
            . "is missing the expected `$prefix` prefix."
        );
    }
}
