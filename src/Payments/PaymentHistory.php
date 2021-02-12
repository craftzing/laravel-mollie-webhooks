<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Spatie\WebhookClient\Models\WebhookCall;

interface PaymentHistory
{
    public function hasLatestStatusForPayment(PaymentId $paymentId, string $status, WebhookCall $webhookCall): bool;
}
