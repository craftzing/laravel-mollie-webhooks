<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Queries;

use Craftzing\Laravel\MollieWebhooks\Http\MollieSignatureValidator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\WebhookClient\Models\WebhookCall;

final class MostRecentMollieWebhookCallByPayloadFragment
{
    /**
     * @param array<mixed> $payload
     */
    public function before(WebhookCall $ignoreWebhookCall, array $payload): ?WebhookCall
    {
        return WebhookCall::query()
            ->where('id', '!=', $ignoreWebhookCall->getKey())
            ->where('name', MollieSignatureValidator::NAME)
            ->where(function (Builder $query) use ($payload): void {
                foreach ($payload as $key => $value) {
                    $query->where('payload', 'LIKE', "%\"{$key}\":\"{$value}\"%");
                }
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }
}
