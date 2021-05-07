<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\PersistsChangesToOngoingWebhookCallPayload;
use Craftzing\Laravel\MollieWebhooks\Queries\LatestMollieWebhookCallByResourceId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;

final class WebhookCallOrderHistory implements OrderHistory
{
    use PersistsChangesToOngoingWebhookCallPayload;

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

        // When we couldn't find a previous webhook call for the order having a status in the payload, we should
        // assume that the ongoing webhook call was triggered due to an order status change. Therefore, we
        // should persist the freshly retrieved status to the payload of the ongoing webhook call in
        // order to have it as the latest status for that order for future webhook calls.
        if (! $latestWebhookCall) {
            $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, ['order_status' => $status]);

            return false;
        }

        $latestOrderStatusInHistory = $this->webhookPayload($latestWebhookCall)['order_status'] ?? null;

        // When the latest status for the order in the webhook call history does not match the freshly
        // retrieved status, we should assume that the ongoing webhook call was triggered due to an
        // order status change. So once again, we should persist the freshly retrieved status
        // to the payload of the ongoing webhook call for future reference...
        if ($latestOrderStatusInHistory !== $status) {
            $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, ['order_status' => $status]);

            return false;
        }

        // When the latest status for the order in the webhook call history DOES match the freshly
        // retrieved status, we should assume that the ongoing webhook call wasn't triggered due
        // to an order status change. Hence, we SHOULDN'T persist it to the payload.
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

        // When we couldn't find a previous webhook call for the order having the refund in the payload, we should
        // assume that the ongoing webhook call was triggered due to an order refund transfer. Therefore, we
        // should persist the freshly retrieved refund to the payload of the ongoing webhook call in
        // order to have it as the settled refund for that order for future webhook calls.
        if (! $latestWebhookCall) {
            $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, compact('refund'));

            return false;
        }

        // When the webhook call history has the settled refund for the order, we should
        // assume that the ongoing webhook call was not triggered due to an order
        // refund transfer. Hence, we SHOULDN'T persist it to the payload.
        return true;
    }
}
