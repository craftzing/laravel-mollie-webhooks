<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Spatie\WebhookClient\Models\WebhookCall;

interface PaymentHistory
{
    public function hasLatestStatusForPayment(
        PaymentId $paymentId,
        string $status,
        WebhookCall $ongoingWebhookCall
    ): bool;

    public function hasRefundWithStatusForPayment(
        PaymentId $paymentId,
        RefundId $refundId,
        string $refundStatus,
        WebhookCall $ongoingWebhookCall
    ): bool;
}
