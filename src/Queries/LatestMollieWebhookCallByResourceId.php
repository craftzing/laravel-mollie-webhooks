<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Queries;

use Craftzing\Laravel\MollieWebhooks\Http\MollieSignatureValidator;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Spatie\WebhookClient\Models\WebhookCall;

final class LatestMollieWebhookCallByResourceId
{
    public function find(ResourceId $resourceId, WebhookCall $ignoreWebhookCall): ?WebhookCall
    {
        return WebhookCall::query()
            ->where('id', '!=', $ignoreWebhookCall->getKey())
            ->where('name', MollieSignatureValidator::NAME)
            ->where('payload', 'LIKE', "%\"id\":\"{$resourceId->value()}\"%")
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }
}
