<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Support\Arr;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Resources\Payment;

final class FakePaymentsEndpoint extends PaymentEndpoint
{
    use FakesMollie;

    /**
     * @var \Mollie\Api\Resources\Payment[]
     */
    private array $payments = [];

    /**
     * {@inheritdoc}
     */
    public function fakePaymentWithStatus(string $status): Payment
    {
        $payment = new Payment($this->client);
        $payment->id = $this->paymentId()->value();
        $payment->status = $status;

        return $this->payments[] = $payment;
    }

    /**
     * {@inheritdoc}
     */
    public function get($paymentId, array $parameters = []): Payment
    {
        return Arr::first($this->payments, fn (Payment $payment) => $payment->id === $paymentId);
    }
}
