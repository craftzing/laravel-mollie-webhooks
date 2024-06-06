<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;

final class MolliePaymentStatusChangedToFailed
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {}
}
