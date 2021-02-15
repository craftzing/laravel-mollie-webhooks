<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;

trait ExposesPaymentStatus
{
    /**
     * @readonly
     */
    public PaymentId $paymentId;

    /**
     * @readonly
     */
    public string $status;

    public function __construct(PaymentId $paymentId, string $status)
    {
        $this->paymentId = $paymentId;
        $this->status = $status;
    }
}
