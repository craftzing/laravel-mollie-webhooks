<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\PaymentProphecy;
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
    public function fakePayment(PaymentProphecy $prophecy): Payment
    {
        return $this->payments[] = $prophecy->apply(new Payment($this->client));
    }

    /**
     * {@inheritdoc}
     */
    public function get($paymentId, array $parameters = []): Payment
    {
        foreach ($this->payments as $payment) {
            if ($payment->id === $paymentId) {
                return $payment;
            }
        }
    }
}
