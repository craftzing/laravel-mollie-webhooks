<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Spatie\WebhookClient\Models\WebhookCall;

final class FakePaymentHistory implements PaymentHistory
{
    private ?string $latestStatus = null;

    public function hasLatestStatusForPayment(PaymentId $paymentId, string $status, WebhookCall $webhookCall): bool
    {
        return $this->latestStatus === $status;
    }

    public function fakeLatestStatus(string $status): void
    {
        $this->latestStatus = $status;
    }
}
