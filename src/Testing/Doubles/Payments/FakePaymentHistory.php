<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Spatie\WebhookClient\Models\WebhookCall;

final class FakePaymentHistory implements PaymentHistory
{
    private ?string $latestStatus = null;

    public function hasLatestStatusForPayment(
        PaymentId $paymentId,
        string $status,
        WebhookCall $ongoingWebhookCall
    ): bool {
        return $this->latestStatus === $status;
    }

    public function fakeLatestStatus(string $status): void
    {
        $this->latestStatus = $status;
    }

    public function hasTransferredRefundForPayment(
        PaymentId $paymentId,
        RefundId $refundId,
        WebhookCall $ongoingWebhookCall
    ): bool {
        // TODO: Implement hasRefundForPayment() method.
    }
}
