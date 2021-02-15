<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Queries\LatestMollieWebhookCallByResourceId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;

final class WebhookCallPaymentHistory implements PaymentHistory
{
    private LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId;

    public function __construct(LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId)
    {
        $this->latestMollieWebhookCallByResourceId = $latestMollieWebhookCallByResourceId;
    }

    public function hasLatestStatusForPayment(
        PaymentId $paymentId,
        string $status,
        WebhookCall $ongoingWebhookCall
    ): bool {
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $paymentId,
            $ongoingWebhookCall,
            WebhookPayloadFragment::fromKeys('status'),
        );

        // When we couldn't find a previous webhook call for the payment having a status in the payload, we should
        // assume that the ongoing webhook call was triggered due to a payment status change. Therefore, we
        // should persist the freshly retrieved status to the payload of the ongoing webhook call in
        // order to have it as the latest status for that payment for future webhook calls.
        if (! $latestWebhookCall) {
            $this->persistStatusToOngoingWebhookCallPayload($ongoingWebhookCall, compact('status'));

            return false;
        }

        $latestPaymentStatusInHistory = $this->webhookPayload($latestWebhookCall)['status'] ?? null;

        // When the latest status for the payment in the webhook call history does not match the freshly
        // retrieved status, we should assume that the ongoing webhook call was triggered due to a
        // payment status change. So once again, we should persist the freshly retrieved status
        // to the payload of the ongoing webhook call for future reference...
        if ($latestPaymentStatusInHistory !== $status) {
            $this->persistStatusToOngoingWebhookCallPayload($ongoingWebhookCall, compact('status'));

            return false;
        }

        // When the latest status for the payment in the webhook call history DOES match the freshly
        // retrieved status, we should assume that the ongoing webhook call wasn't triggered due
        // to a payment status change. Hence, we SHOULDN'T persist it to the payload.
        return true;
    }

    /**
     * @param array<mixed> $additionalPayload
     */
    private function persistStatusToOngoingWebhookCallPayload(WebhookCall $webhookCall, array $additionalPayload): void
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
