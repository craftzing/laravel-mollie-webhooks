<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Exception;

final class AppMisconfigured extends Exception
{
    public static function missingConfigValue(): self
    {
        return new self(
            'Please make sure to provide a valid value by either setting the ' .
            '`laravel-mollie-webhooks.value` config or the according environment variable.'
        );
    }
}
