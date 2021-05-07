<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Contracts\Container\Container;
use Mollie\Api\Resources\Order;
use Mollie\Api\Types\OrderStatus;

use function array_merge;

final class FakeOrder extends Order
{
    use FakesMollie;

    public const STATUSES = [
        OrderStatus::STATUS_CREATED,
        OrderStatus::STATUS_PAID,
        OrderStatus::STATUS_AUTHORIZED,
        OrderStatus::STATUS_CANCELED,
        OrderStatus::STATUS_SHIPPING,
        OrderStatus::STATUS_COMPLETED,
        OrderStatus::STATUS_EXPIRED,
        OrderStatus::STATUS_PENDING,
    ];

    private Container $container;

    /**
     * @var \Mollie\Api\Resources\Refund[]
     */
    public array $refunds = [];

    public static function fake(Container $container): self
    {
        $instance = new self($container->get(FakeMollieApiClient::class));
        $instance->id = $instance->generateOrderId()->value();
        $instance->container = $container;

        /* @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeOrdersEndpoint $orders */
        $orders = $container->get(FakeOrdersEndpoint::class);

        return $orders->order = $instance;
    }

    public function id(): OrderId
    {
        return OrderId::fromString($this->id);
    }

    public function withStatus(string $status = ''): self
    {
        $this->status = $status ?: $this->randomOrderStatusExcept();

        return $this;
    }

    public function withRefunds(FakeRefund ...$refunds): self
    {
        $refunds ??= [FakeRefund::fake($this->container)];

        $this->refunds = array_merge($this->refunds, $refunds);

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
