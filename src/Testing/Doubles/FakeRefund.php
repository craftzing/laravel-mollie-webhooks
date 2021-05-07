<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToFailed;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToPending;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToProcessing;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToQueued;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundWasTransferred;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Contracts\Container\Container;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Types\RefundStatus;

final class FakeRefund extends Refund
{
    use FakesMollie;

    public const STATUSES = [
        RefundStatus::STATUS_QUEUED,
        RefundStatus::STATUS_PENDING,
        RefundStatus::STATUS_PROCESSING,
        RefundStatus::STATUS_REFUNDED,
        RefundStatus::STATUS_FAILED,
    ];

    public const STATUS_EVENTS = [
        RefundStatus::STATUS_QUEUED => MollieRefundStatusChangedToQueued::class,
        RefundStatus::STATUS_PENDING => MollieRefundStatusChangedToPending::class,
        RefundStatus::STATUS_PROCESSING => MollieRefundStatusChangedToProcessing::class,
        RefundStatus::STATUS_REFUNDED => MollieRefundWasTransferred::class,
        RefundStatus::STATUS_FAILED => MollieRefundStatusChangedToFailed::class,
    ];

    /**
     * @return static
     */
    public static function fake(Container $container): self
    {
        $instance = new self($container->get(FakeMollieApiClient::class));
        $instance->id = $instance->generateRefundId()->value();

        return $instance;
    }

    public function id(): RefundId
    {
        return RefundId::fromString($this->id);
    }

    public function withStatus(string $status = ''): self
    {
        $this->status = $status ?: $this->randomRefundStatusExcept();

        return $this;
    }

    public function notTransferred(): self
    {
        $this->status = $this->randomRefundStatusExcept(RefundStatus::STATUS_REFUNDED);

        return $this;
    }

    public function transferred(): self
    {
        return $this->withStatus(RefundStatus::STATUS_REFUNDED);
    }

    public function statusEventClass(): string
    {
        return self::STATUS_EVENTS[$this->status];
    }
}
