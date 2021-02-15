<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Mollie\Api\Types\PaymentStatus;

use function json_encode;

final class WebhookCallPaymentHistoryTest extends IntegrationTestCase
{
    public function webhookCallHistory(): Generator
    {
        yield 'No webhook calls were made for the payment so far' => [
            fn (): bool => false,
        ];

        yield 'Latest payment status in the webhook call history differs from the one in the current webhook call' => [
            function (PaymentId $paymentId, string $status): bool {
                $latestStatus = $this->randomPaymentStatusExcept($status);
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withStatusInPayload($latestStatus)
                    ->create();

                return false;
            },
        ];

        yield 'Latest payment status in the webhook call history matches from the one in the current webhook call' => [
            function (PaymentId $paymentId, string $status): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withStatusInPayload($status)
                    ->create();

                return true;
            },
        ];

        yield 'Latest webhook call for the payment did not include the status, but a webhook call before it does' => [
            function (PaymentId $paymentId, string $status): bool {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withStatusInPayload($status)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->create();

                return true;
            },
        ];
    }

    /**
     * @test
     * @dataProvider webhookCallHistory
     */
    public function itCanCheckIfItHasALatestStatusForAPayment(callable $resolveExpectedResult): void
    {
        $paymentId = $this->paymentId();
        $status = PaymentStatus::STATUS_PAID;
        $expectedToHaveSameLatestStatus = $resolveExpectedResult($paymentId, $status);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($paymentId)
            ->create();

        $result = $this->app[WebhookCallPaymentHistory::class]->hasLatestStatusForPayment(
            $paymentId,
            $status,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveSameLatestStatus, $result);
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode($this->expectedPayload($paymentId, $status, $expectedToHaveSameLatestStatus)),
        ]);
    }

    private function expectedPayload(PaymentId $paymentId, string $status, bool $expectedToHaveSameLatestStatus): array
    {
        $expectedPayload = ['id' => $paymentId->value()];

        // Only when the PaymentHistory is not expected to have the same latest status, we should
        // expect the freshly retrieved status to be persisted to the ongoing webhook call payload.
        if (! $expectedToHaveSameLatestStatus) {
            $expectedPayload['status'] = $status;
        }

        return $expectedPayload;
    }
}
