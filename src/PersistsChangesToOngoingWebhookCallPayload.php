<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Spatie\WebhookClient\Models\WebhookCall;

use function array_merge;

trait PersistsChangesToOngoingWebhookCallPayload
{
    /**
     * @param array<mixed> $additionalPayload
     */
    private function persistChangeToOngoingWebhookCallPayload(WebhookCall $webhookCall, array $additionalPayload): void
    {
        $webhookCall->update(['payload' => array_merge($this->webhookPayload($webhookCall), $additionalPayload)]);
    }

    /**
     * @return array<mixed>
     */
    private function webhookPayload(WebhookCall $webhookCall): array
    {
        return $webhookCall->getAttribute('payload') ?: [];
    }
}
