<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Support\Arr;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;
use function factory;
use function tap;

final class FakeMollieWebhookCall
{
    use FakesMollie;

    public const TABLE = 'webhook_calls';

    public const PAYMENT_STATUSES = [
        PaymentStatus::STATUS_OPEN,
        PaymentStatus::STATUS_PENDING,
        PaymentStatus::STATUS_AUTHORIZED,
        PaymentStatus::STATUS_PAID,
        PaymentStatus::STATUS_EXPIRED,
        PaymentStatus::STATUS_FAILED,
        PaymentStatus::STATUS_CANCELED,
    ];

    public const REFUND_STATUSES = [
        RefundStatus::STATUS_QUEUED,
        RefundStatus::STATUS_PENDING,
        RefundStatus::STATUS_PROCESSING,
        RefundStatus::STATUS_REFUNDED,
        RefundStatus::STATUS_FAILED,
    ];

    /**
     * @var mixed
     */
    private array $payload = [];

    private function __construct()
    {
        $this->payload = ['id' => $this->generatePaymentId()->value()];
    }

    public static function new(): self
    {
        return new self();
    }

    public function forResourceId(ResourceId $resourceId): self
    {
        return tap(clone $this, fn (self $instance) => $instance->payload['id'] = $resourceId->value());
    }

    public function withStatusInPayload(string $status = ''): self
    {
        if (! $status) {
            $status = Arr::random(self::PAYMENT_STATUSES);
        }

        return $this->appendToPayload(compact('status'));
    }

    public function withRefundInPayload(?RefundId $refundId = null, string $status = ''): self
    {
        if (! $refundId) {
            $refundId = $this->generateRefundId();
        }

        if (! $status) {
            $status = RefundStatus::STATUS_REFUNDED;
        }

        return $this->appendToPayload([
            'refund' => [
                'id' => $refundId->value(),
                'status' => $status,
            ],
        ]);
    }

    public function appendToPayload(array $payload): self
    {
        return tap(
            clone $this,
            fn (self $instance) => $instance->payload = array_merge($payload, $instance->payload),
        );
    }

    public function create(array $attributes = []): WebhookCall
    {
        return factory(WebhookCall::class)->create($attributes + ['payload' => $this->payload]);
    }
}
