<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;

final class MolliePaymentStatusChangedToCanceled
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {}
}
