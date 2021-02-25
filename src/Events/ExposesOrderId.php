<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;

trait ExposesOrderId
{
    /**
     * @readonly
     */
    public OrderId $orderId;

    public function __construct(OrderId $orderId)
    {
        $this->orderId = $orderId;
    }
}