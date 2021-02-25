<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\Queries\LatestMollieWebhookCallByResourceId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function array_merge;
use function compact;

final class WebhookCallOrderHistory implements OrderHistory
{
    private LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId;

    public function __construct(LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId)
    {
        $this->latestMollieWebhookCallByResourceId = $latestMollieWebhookCallByResourceId;
    }

    public function hasLatestStatusForOrder(
        OrderId $orderId,
        string $status,
        WebhookCall $ongoingWebhookCall
    ): bool {
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $orderId,
            $ongoingWebhookCall,
            WebhookPayloadFragment::fromKeys('order_status'),
        );

        // When we couldn't find a previous webhook call for the payment having a status in the payload, we should
        // assume that the ongoing webhook call was triggered due to a payment status change. Therefore, we
        // should persist the freshly retrieved status to the payload of the ongoing webhook call in
        // order to have it as the latest status for that payment for future webhook calls.
        if (! $latestWebhookCall) {
            $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, ['order_status' => $status]);

            return false;
        }

        $latestPaymentStatusInHistory = $this->webhookPayload($latestWebhookCall)['order_status'] ?? null;

        // When the latest status for the payment in the webhook call history does not match the freshly
        // retrieved status, we should assume that the ongoing webhook call was triggered due to a
        // payment status change. So once again, we should persist the freshly retrieved status
        // to the payload of the ongoing webhook call for future reference...
        if ($latestPaymentStatusInHistory !== $status) {
            $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, ['order_status' => $status]);

            return false;
        }

        // When the latest status for the payment in the webhook call history DOES match the freshly
        // retrieved status, we should assume that the ongoing webhook call wasn't triggered due
        // to a payment status change. Hence, we SHOULDN'T persist it to the payload.
        return true;
    }

    public function hasTransferredRefundForOrder(
        OrderId $orderId,
        RefundId $refundId,
        WebhookCall $ongoingWebhookCall
    ): bool {
        $refund = [
            'id' => $refundId->value(),
            'refund_status' => RefundStatus::STATUS_REFUNDED,
        ];
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $orderId,
            $ongoingWebhookCall,
            WebhookPayloadFragment::fromValues($refund),
        );

        // When we couldn't find a previous webhook call for the payment having the refund in the payload, we should
        // assume that the ongoing webhook call was triggered due to a payment refund transfer. Therefore, we
        // should persist the freshly retrieved refund to the payload of the ongoing webhook call in
        // order to have it as the settled refund for that payment for future webhook calls.
        if (! $latestWebhookCall) {
            $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, compact('refund'));

            return false;
        }

        // When the webhook call history has the settled refund for the payment, we should
        // assume that the ongoing webhook call was not triggered due to a payment
        // refund transfer. Hence, we SHOULDN'T persist it to the payload.
        return true;
    }

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
