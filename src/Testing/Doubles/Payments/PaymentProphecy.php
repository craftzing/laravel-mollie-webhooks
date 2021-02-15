<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Mollie\Api\Resources\Payment;

final class PaymentProphecy
{
    use FakesMollie;

    /**
     * @var array<callable>
     */
    private array $prophecies = [];

    public static function make(): self
    {
        return new self();
    }

    public function apply(Payment $payment): Payment
    {
        foreach ($this->prophecies as $prophecy) {
            $prophecy($payment);
        }

        return $payment;
    }

    public function id(PaymentId $id): self
    {
        $this->prophecies[] = fn (Payment $payment) => $payment->id = $id->value();

        return $this;
    }

    public function status(string $status): self
    {
        $this->prophecies[] = fn (Payment $payment) => $payment->status = $status;

        return $this;
    }
}
