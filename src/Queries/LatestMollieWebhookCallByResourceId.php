<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Queries;

use Closure;
use Craftzing\Laravel\MollieWebhooks\Http\MollieSignatureValidator;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Illuminate\Database\Eloquent\Builder;
use Spatie\WebhookClient\Models\WebhookCall;

final class LatestMollieWebhookCallByResourceId
{
    public function find(
        ResourceId $resourceId,
        WebhookCall $ignoreWebhookCall,
        ?WebhookPayloadFragment $payloadFragment = null
    ): ?WebhookCall {
        return WebhookCall::query()
            ->where('id', '!=', $ignoreWebhookCall->getKey())
            ->where('name', MollieSignatureValidator::NAME)
            ->where('payload', 'LIKE', "%\"id\":\"{$resourceId->value()}\"%")
            ->when($payloadFragment, Closure::fromCallable([$this, 'filterByPayloadFragment']))
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    private function filterByPayloadFragment(Builder $query, WebhookPayloadFragment $payloadFragment): void
    {
        foreach ($payloadFragment->keys() as $key) {
            $query->where('payload', 'LIKE', "%\"{$key}\":%");
        }

        foreach ($payloadFragment->values() as $key => $value) {
            $query->where('payload', 'LIKE', "%\"{$key}\":\"{$value}\"%");
        }
    }
}
