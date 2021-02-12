<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Queries\LatestMollieWebhookCallByResourceId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Mollie\Api\Resources\Payment;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;

final class WebhookCallPaymentHistory implements PaymentHistory
{
    private LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId;

    public function __construct(LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId)
    {
        $this->latestMollieWebhookCallByResourceId = $latestMollieWebhookCallByResourceId;
    }

    public function hasLatestStatusForPayment(PaymentId $paymentId, string $status, WebhookCall $webhookCall): bool
    {
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $paymentId,
            $webhookCall,
            WebhookPayloadFragment::fromKeys('status'),
        );

        // We should append the status to the current webhook call in order to
        // keep track of the payment status within the webhook call history.
        $this->appendStatusToWebhookCallPayload($webhookCall, compact('status'));

        if (! $latestWebhookCall) {
            return false;
        }

        $latestPaymentStatusInHistory = $this->webhookPayload($latestWebhookCall)['status'] ?? null;

        return $latestPaymentStatusInHistory === $status;
    }

    /**
     * @param array<mixed> $additionalPayload
     */
    private function appendStatusToWebhookCallPayload(WebhookCall $webhookCall, array $additionalPayload): void
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
