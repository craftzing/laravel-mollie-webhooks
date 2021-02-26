<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use Illuminate\Contracts\Foundation\Application;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;

final class FakeOrdersEndpoint extends OrderEndpoint
{
    use FakesMollie;

    public ?Order $order = null;

    public static function fake(Application $app): self
    {
        return $app->instance(self::class, new self($app[MollieApiClient::class]));
    }

    /**
     * {@inheritdoc}
     */
    public function get($orderId, array $parameters = []): Order
    {
        return $this->order;
    }
}
