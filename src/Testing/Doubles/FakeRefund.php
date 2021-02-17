<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Contracts\Container\Container;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Types\RefundStatus;

final class FakeRefund extends Refund
{
    use FakesMollie;

    /**
     * @return static
     */
    public static function fake(Container $container): self
    {
        $instance = new self($container->get(FakeMollieApiClient::class));
        $instance->id = $instance->generateRefundId()->value();

        return $instance;
    }

    public function transferred(): self
    {
        $this->status = RefundStatus::STATUS_REFUNDED;

        return $this;
    }
}
