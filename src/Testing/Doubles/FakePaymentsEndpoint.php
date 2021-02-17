<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Contracts\Foundation\Application;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;

final class FakePaymentsEndpoint extends PaymentEndpoint
{
    use FakesMollie;

    public ?Payment $payment = null;

    public static function fake(Application $app): self
    {
        return $app->instance(self::class, new self($app[MollieApiClient::class]));
    }

    /**
     * @var \Mollie\Api\Resources\Payment[]
     */
    private array $payments = [];

    /**
     * {@inheritdoc}
     */
    public function get($paymentId, array $parameters = []): Payment
    {
        return $this->payment;
    }
}
