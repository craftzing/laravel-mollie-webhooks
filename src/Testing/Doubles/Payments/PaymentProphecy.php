<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Mollie\Api\Resources\Payment;

final class PaymentProphecy
{
    use FakesMollie;

    /**
     * @readonly
     */
    public Payment $payment;

    public function __construct(Payment $payment)
    {
        $payment->id = $this->paymentId()->value();
        $payment->status = $this->randomPaymentStatusExcept();

        $this->payment = $payment;
    }

    public function withStatus(string $status): self
    {
        $this->payment->status = $status;

        return $this;
    }
}
