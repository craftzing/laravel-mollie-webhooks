<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToFailed;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToPending;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToProcessing;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToQueued;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToRefunded;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Illuminate\Contracts\Events\Dispatcher;
use Mollie\Api\Resources\Refund;
use Spatie\WebhookClient\Models\WebhookCall;

trait DispatchesRefundEventsForResources
{
    private Dispatcher $events;

    abstract protected function hasRefundWithStatusForResource(
        ResourceId $resourceId,
        RefundId $refundId,
        string $refundStatus,
        WebhookCall $ongoingWebhookCall
    ): bool;

    private function dispatchRefundEvents(ResourceId $resourceId, Refund $refund, WebhookCall $ongoingWebhookCall): void
    {
        $refundId = RefundId::fromString($refund->id);
        $hasRefundWithStatusForOrder = $this->hasRefundWithStatusForResource(
            $resourceId,
            $refundId,
            $refund->status,
            $ongoingWebhookCall,
        );

        if ($hasRefundWithStatusForOrder) {
            return;
        }

        if ($refund->isQueued()) {
            $this->events->dispatch(new MollieRefundStatusChangedToQueued($refundId, $resourceId));

            return;
        }

        if ($refund->isPending()) {
            $this->events->dispatch(new MollieRefundStatusChangedToPending($refundId, $resourceId));

            return;
        }

        if ($refund->isProcessing()) {
            $this->events->dispatch(new MollieRefundStatusChangedToProcessing($refundId, $resourceId));

            return;
        }

        if ($refund->isTransferred()) {
            $this->events->dispatch(new MollieRefundStatusChangedToRefunded($refundId, $resourceId));

            return;
        }

        if ($refund->isFailed()) {
            $this->events->dispatch(new MollieRefundStatusChangedToFailed($refundId, $resourceId));

            return;
        }
    }
}
