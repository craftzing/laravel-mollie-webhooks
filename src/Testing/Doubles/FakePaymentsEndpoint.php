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
     * @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\PaymentProphecy[]
     */
    private array $paymentProphecies = [];

    /**
     * {@inheritdoc}
     */
    public function fakePayment(): PaymentProphecy
    {
        $payment = new Payment($this->client);

        return $this->paymentProphecies[] = new PaymentProphecy($payment);
    }

    /**
     * {@inheritdoc}
     */
    public function get($paymentId, array $parameters = []): Payment
    {
        foreach ($this->paymentProphecies as $prophecy) {
            if ($prophecy->payment->id === $paymentId) {
                return $prophecy->payment;
            }
        }
    }
}
