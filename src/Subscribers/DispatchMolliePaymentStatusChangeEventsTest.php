<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToExpired;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToFailed;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToPaid;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\FakePaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\PaymentStatus;
use Spatie\WebhookClient\Models\WebhookCall;

final class DispatchMolliePaymentStatusChangeEventsTest extends IntegrationTestCase
{
    private FakePaymentHistory $fakePaymentHistory;

    /**
     * @before
     */
    public function fakePaymentHistory(): void
    {
        $this->afterApplicationCreated(function () {
            $this->swap(PaymentHistory::class, $this->fakePaymentHistory = new FakePaymentHistory());
        });
    }

    /**
     * @test
     */
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericPaymentEvent(): void
    {
        $this->dontFakeEvents();

        Event::subscribe(DispatchMolliePaymentStatusChangeEvents::class);
        Event::dispatch(new MolliePaymentWasUpdated($this->paymentId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === DispatchMolliePaymentStatusChangeEvents::class;
        });
    }

    public function paymentHistory(): Generator
    {
        yield 'Payment history does not have status for the payment yet' => [
            fn () => null,
        ];

        foreach (FakeMollieWebhookCall::PAYMENT_STATUSES as $paymentStatus) {
            yield "Payment history has `$paymentStatus` as the latest status for the payment" => [
                function (FakePaymentHistory $fakePaymentHistory) use ($paymentStatus): string {
                    $fakePaymentHistory->fakeLatestStatus($paymentStatus);

                    return $paymentStatus;
                },
            ];
        }
    }

    /**
     * @test
     * @dataProvider paymentHistory
     */
    public function itCanHandleWebhookCallsIndicatingAPaymentStatusChangedToPaid(callable $addPaymentHistory): void
    {
        $paid = PaymentStatus::STATUS_PAID;
        $latestStatusInPaymentHistory = $addPaymentHistory($this->fakePaymentHistory);
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($paid, $addPaymentHistory);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[DispatchMolliePaymentStatusChangeEvents::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $paid) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToPaid::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToPaid::class,
                new TruthTest(function (MolliePaymentStatusChangedToPaid $event) use ($paymentId, $paid): void {
                    $this->assertSame($paymentId, $event->paymentId);
                    $this->assertSame($paid, $event->status);
                }),
            );
        }
    }

    /**
     * @test
     * @dataProvider paymentHistory
     */
    public function itCanHandleWebhookCallsIndicatingAPaymentStatusChangedToExpired(callable $addPaymentHistory): void
    {
        $expired = PaymentStatus::STATUS_EXPIRED;
        $latestStatusInPaymentHistory = $addPaymentHistory($this->fakePaymentHistory);
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($expired, $addPaymentHistory);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[DispatchMolliePaymentStatusChangeEvents::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $expired) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToExpired::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToExpired::class,
                new TruthTest(function (MolliePaymentStatusChangedToExpired $event) use ($paymentId, $expired): void {
                    $this->assertSame($paymentId, $event->paymentId);
                    $this->assertSame($expired, $event->status);
                }),
            );
        }
    }

    /**
     * @test
     * @dataProvider paymentHistory
     */
    public function itCanHandleWebhookCallsIndicatingAPaymentStatusChangedToFailed(callable $addPaymentHistory): void
    {
        $failed = PaymentStatus::STATUS_FAILED;
        $latestStatusInPaymentHistory = $addPaymentHistory($this->fakePaymentHistory);
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($failed, $addPaymentHistory);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[DispatchMolliePaymentStatusChangeEvents::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $failed) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToExpired::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToFailed ::class,
                new TruthTest(function (MolliePaymentStatusChangedToFailed $event) use ($paymentId, $failed): void {
                    $this->assertSame($paymentId, $event->paymentId);
                    $this->assertSame($failed, $event->status);
                }),
            );
        }
    }

    private function webhookCallIndicatingPaymentStatusChangedTo(
        string $paymentStatus,
        callable $addPaymentHistory
    ): WebhookCall {
        $payment = $this->fakeMolliePayments->fakePaymentWithStatus($paymentStatus);

        return FakeMollieWebhookCall::new()
            ->forResourceId(PaymentId::fromString($payment->id))
            ->create();
    }
}
