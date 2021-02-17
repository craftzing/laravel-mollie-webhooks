<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Contracts\Container\Container;
use Mollie\Api\Resources\Payment;

final class FakePayment extends Payment
{
    use FakesMollie;

    private Container $container;

    /**
     * @var \Mollie\Api\Resources\Refund[]
     */
    public array $refunds = [];

    public static function fake(Container $container): self
    {
        $instance = new self($container->get(FakeMollieApiClient::class));
        $instance->id = $instance->generatePaymentId()->value();
        $instance->container = $container;

        /* @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePaymentsEndpoint $payments */
        $payments = $container->get(FakePaymentsEndpoint::class);

        return $payments->payment = $instance;
    }

    public function id(): PaymentId
    {
        return PaymentId::fromString($this->id);
    }

    public function withStatus(string $status = ''): self
    {
        $this->status = $status ?: $this->randomPaymentStatusExcept();

        return $this;
    }

    public function withRefund(?FakeRefund $refund = null): self
    {
        $refund = $refund ?: FakeRefund::fake($this->container);

        $this->refunds[] = $refund;

        return $this;
    }

    public function hasRefunds(): bool
    {
        return ! empty($this->refunds);
    }

    public function refunds()
    {
        return $this->refunds;
    }
}
