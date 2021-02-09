<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Exceptions;

use Spatie\WebhookClient\Exceptions\WebhookFailed as SpatieWebhookFailed;

final class UnexpectedWebhookPayload extends SpatieWebhookFailed
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
