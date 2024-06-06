<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Exception;

final class UnexpectedWebhookPayload extends Exception
{
    public static function missingObjectIdentifier(): self
    {
        return new self('The webhook payload is missing an `id` property.');
    }

    public static function objectIdentifierCannotBeMappedToAMollieResource(): self
    {
        return new self('The `id` provided in the webhook payload cannot be mapped to a Mollie resource.');
    }
}
