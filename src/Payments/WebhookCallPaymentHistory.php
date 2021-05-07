<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\PersistsChangesToOngoingWebhookCallPayload;
use Craftzing\Laravel\MollieWebhooks\Queries\LatestMollieWebhookCallByResourceId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;

final class WebhookCallPaymentHistory implements PaymentHistory
{
    use PersistsChangesToOngoingWebhookCallPayload;

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
            WebhookPayloadFragment::fromValues(['payment_status' => $status]),
        );

        // When the latest status for the payment in the webhook call history matches the freshly
        // retrieved status, we should assume that the ongoing webhook call wasn't triggered
        // due to a payment status change. Hence, we shouldn't persist it to the payload.
        if ($latestWebhookCall) {
            return true;
        }

        // When we couldn't find a previous webhook call for the payment having a status in the payload, we should
        // assume that the ongoing webhook call was triggered due to a payment status change. Therefore, we
        // should persist the freshly retrieved status to the payload of the ongoing webhook call in
        // order to have it as the latest status for that payment for future webhook calls.
        $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, ['payment_status' => $status]);

        return false;
    }

    public function hasTransferredRefundForPayment(
        PaymentId $paymentId,
        RefundId $refundId,
        WebhookCall $ongoingWebhookCall
    ): bool {
        $refund = [
            'id' => $refundId->value(),
            'refund_status' => RefundStatus::STATUS_REFUNDED,
        ];
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $paymentId,
            $ongoingWebhookCall,
            WebhookPayloadFragment::fromValues($refund),
        );

        // When the webhook call history has the settled refund for the payment, we should
        // assume that the ongoing webhook call was not triggered due to a payment
        // refund transfer. Hence, we SHOULDN'T persist it to the payload.
        if ($latestWebhookCall) {
            return true;
        }

        // When we couldn't find a previous webhook call for the payment having the refund in the payload, we should
        // assume that the ongoing webhook call was triggered due to a payment refund transfer. Therefore, we
        // should persist the freshly retrieved refund to the payload of the ongoing webhook call in
        // order to have it as the settled refund for that payment for future webhook calls.
        $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, compact('refund'));

        return false;
    }
}
