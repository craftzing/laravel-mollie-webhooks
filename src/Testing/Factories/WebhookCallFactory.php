<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Factories;

use Carbon\CarbonImmutable;
use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Mollie\Api\Types\PaymentStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;
use function tap;

/**
 * @method static self new($attributes = [])
 * @method \Spatie\WebhookClient\Models\WebhookCall|\Spatie\WebhookClient\Models\WebhookCall[] create($attributes = [], ?Model $parent = null)
 * @method \Spatie\WebhookClient\Models\WebhookCall|\Spatie\WebhookClient\Models\WebhookCall[] make($attributes = [], ?Model $parent = null)
 */
final class WebhookCallFactory extends Factory
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

    /**
     * @var string
     */
    protected $model = WebhookCall::class;

    /**
     * @var mixed
     */
    private array $payload = [];

    protected function newInstance(array $arguments = []): self
    {
        $instance = parent::newInstance($arguments);
        $instance->payload = $this->payload;

        return $instance;
    }

    public function configure(): self
    {
        $this->payload = ['id' => $this->paymentId()->value()];

        return $this;
    }

    public function definition(): array
    {
        return [
            'name' => 'mollie',
            'payload' => $this->payload,
            'created_at' => CarbonImmutable::now(),
        ];
    }

    public function forPaymentId(PaymentId $paymentId): self
    {
        return tap(self::newInstance(), fn (self $instance) => $instance->payload['id'] = $paymentId->value());
    }

    public function withStatusInPayload(string $status = ''): self
    {
        if (! $status) {
            $status = $this->faker->randomElement(self::PAYMENT_STATUSES);
        }

        return $this->appendToPayload(compact('status'));
    }

    public function appendToPayload(array $payload): self
    {
        return tap(self::newInstance(), fn (self $instance) => $instance->payload = $payload + $instance->payload);
    }
}
