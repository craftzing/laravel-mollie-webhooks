<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Concerns;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
use Generator;
use Spatie\WebhookClient\Models\WebhookCall;

use function json_encode;

trait ProvidesResourceWebhookCallHistory
{
    public function resourceWebhookCallHistory(string $resourceStatus, callable $resolveDifferentStatus): Generator
    {
        yield "$resourceStatus - No webhook calls were made for the resource so far" => [
            fn (): string => $resourceStatus,
            false,
        ];

        yield "$resourceStatus - Latest resource status in the webhook call history differs from the one in the current webhook call" => [
            function (ResourceId $resourceId) use ($resourceStatus, $resolveDifferentStatus): string {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withResourceStatusInPayload($resolveDifferentStatus($resourceStatus))
                    ->create();

                return $resourceStatus;
            },
            false,
        ];

        yield "$resourceStatus - Latest resource status in the webhook call history matches from the one in the current webhook call" => [
            function (ResourceId $resourceId) use ($resourceStatus): string {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withResourceStatusInPayload($resourceStatus)
                    ->create();

                return $resourceStatus;
            },
            true,
        ];

        yield "$resourceStatus - Latest webhook call for the resource did not include the status, but a webhook call before it does" => [
            function (ResourceId $resourceId) use ($resourceStatus): string {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withResourceStatusInPayload($resourceStatus)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->create();

                return $resourceStatus;
            },
            true,
        ];

        yield "$resourceStatus - Latest webhook call for the resource was due to a refund, but the latest status differs" => [
            function (ResourceId $resourceId) use ($resourceStatus, $resolveDifferentStatus): string {
                $latestStatus = $resolveDifferentStatus($resourceStatus);
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withResourceStatusInPayload($latestStatus)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withRefundInPayload()
                    ->create();

                return $resourceStatus;
            },
            false,
        ];

        yield "$resourceStatus - Latest webhook call for the resource was due to a refund, but the latest status is the same" => [
            function (ResourceId $resourceId) use ($resourceStatus): string {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withResourceStatusInPayload($resourceStatus)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($resourceId)
                    ->withRefundInPayload()
                    ->create();

                return $resourceStatus;
            },
            true,
        ];
    }

    public function refundsWebhookCallHistory(string $refundStatus): Generator
    {
        foreach (FakeRefund::STATUSES as $refundStatus) {
            yield "$refundStatus - No webhook calls were made for the resource" => [
                fn(): string => $refundStatus,
                false,
            ];

            yield "$refundStatus - Resource has no refunds in the webhook call history" => [
                function (ResourceId $resourceId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($resourceId)
                        ->create();

                    return $refundStatus;
                },
                false,
            ];

            yield "$refundStatus - Resource has the refund with the same status in the webhook call history" => [
                function (ResourceId $resourceId, RefundId $refundId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($resourceId)
                        ->withRefundInPayload($refundId, $refundStatus)
                        ->create();

                    return $refundStatus;
                },
                true,
            ];

            yield "$refundStatus - Resource has the refund with a different status in the webhook call history" => [
                function (ResourceId $resourceId, RefundId $refundId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($resourceId)
                        ->withRefundInPayload($refundId, $this->randomRefundStatusExcept($refundStatus))
                        ->create();

                    return $refundStatus;
                },
                false,
            ];

            yield "$refundStatus - Resource has a different refund with the same status in the webhook call history" => [
                function (ResourceId $resourceId, RefundId $refundId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($resourceId)
                        ->withRefundInPayload(null, $refundStatus)
                        ->create();

                    return $refundStatus;
                },
                false,
            ];
        }
    }

    private function assertDatabaseHasWebhookCallForResource(WebhookCall $webhookCall, ResourceId $resourceId): void
    {
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode(['id' => $resourceId->value()]),
        ]);
    }

    private function assertDatabaseHasWebhookCallForResourceWithStatus(
        WebhookCall $webhookCall,
        ResourceId $resourceId,
        string $resourceStatus
    ): void {
        $statusKey = 'payment_status';

        if ($resourceId instanceof OrderId) {
            $statusKey = 'order_status';
        }

        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode([
                'id' => $resourceId->value(),
                $statusKey => $resourceStatus,
            ]),
        ]);
    }

    private function assertDatabaseHasWebhookCallForResourceWithRefundStatus(
        WebhookCall $webhookCall,
        ResourceId $resourceId,
        RefundId $refundId,
        string $refundStatus
    ): void {
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode([
                'id' => $resourceId->value(),
                'refund' => [
                    'id' => $refundId->value(),
                    'refund_status' => $refundStatus,
                ],
            ]),
        ]);
    }
}
