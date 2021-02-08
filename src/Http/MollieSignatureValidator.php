<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Http;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

final class MollieSignatureValidator implements SignatureValidator
{
    public const NAME = 'mollie';

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        // Mollie does not provide a signing secret. Instead, they only provide an object ID within the request
        // and require the consumer to call the Mollie API to check whether something has actually changed.
        return true;
    }
}
