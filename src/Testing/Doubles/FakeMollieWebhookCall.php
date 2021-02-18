<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Support\Arr;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;
use function factory;
use function tap;

final class FakeMollieWebhookCall
{
    use FakesMollie;

    public const TABLE = 'webhook_calls';

    /**
     * @var mixed
     */
    private array $payload = [];

    private ?string $exception = null;

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

    public function failed(): self
    {
        return tap(clone $this, fn (self $instance) => $instance->exception = 'Something went wrong');
    }

    public function withStatusInPayload(string $status = ''): self
    {
        if (! $status) {
            $status = Arr::random(FakePayment::STATUSES);
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
        return factory(WebhookCall::class)->create($attributes + [
            'payload' => $this->payload,
            'exception' => $this->exception,
        ]);
    }
}
