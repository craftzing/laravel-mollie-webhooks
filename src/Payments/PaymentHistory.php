<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Mollie\Api\Resources\Payment;

interface PaymentHistory
{
    public function hasLatestStatus(string $status, Payment $payment): bool;
}
