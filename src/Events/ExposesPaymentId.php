<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;

trait ExposesPaymentId
{
    /**
     * @readonly
     */
    public PaymentId $paymentId;

    public function __construct(PaymentId $paymentId)
    {
        $this->paymentId = $paymentId;
    }
}
