<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Mollie\Api\Resources\Payment;

final class FakePaymentHistory implements PaymentHistory
{
    private ?string $latestStatus = null;

    public function hasLatestStatus(string $status, Payment $payment): bool
    {
        return $this->latestStatus === $status;
    }

    public function fakeLatestStatus(string $status): void
    {
        $this->latestStatus = $status;
    }
}
