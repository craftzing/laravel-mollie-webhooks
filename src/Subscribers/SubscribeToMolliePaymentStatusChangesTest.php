<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToCanceled;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToExpired;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToFailed;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToPaid;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePayment;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\FakePaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\PaymentStatus;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMolliePaymentStatusChangesTest extends IntegrationTestCase
{
    private FakePaymentHistory $fakePaymentHistory;

    /**
     * @before
     */
    public function fakePaymentHistory(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->swap(PaymentHistory::class, $this->fakePaymentHistory = new FakePaymentHistory());
        });
    }

    /**
     * @test
     */
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericPaymentEvent(): void
    {
        $this->dontFakeEvents();

        Event::subscribe(SubscribeToMolliePaymentStatusChanges::class);
        Event::dispatch(new MolliePaymentWasUpdated($this->generatePaymentId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === SubscribeToMolliePaymentStatusChanges::class;
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
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($paid);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMolliePaymentStatusChanges::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $paid) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToPaid::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToPaid::class,
                new TruthTest(function (MolliePaymentStatusChangedToPaid $event) use ($paymentId): void {
                    $this->assertSame($paymentId, $event->paymentId);
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
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($expired);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMolliePaymentStatusChanges::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $expired) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToExpired::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToExpired::class,
                new TruthTest(function (MolliePaymentStatusChangedToExpired $event) use ($paymentId): void {
                    $this->assertSame($paymentId, $event->paymentId);
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
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($failed);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMolliePaymentStatusChanges::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $failed) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToExpired::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToFailed::class,
                new TruthTest(function (MolliePaymentStatusChangedToFailed $event) use ($paymentId): void {
                    $this->assertSame($paymentId, $event->paymentId);
                }),
            );
        }
    }

    /**
     * @test
     * @dataProvider paymentHistory
     */
    public function itCanHandleWebhookCallsIndicatingAPaymentStatusChangedToCanceled(callable $addPaymentHistory): void
    {
        $canceled = PaymentStatus::STATUS_CANCELED;
        $latestStatusInPaymentHistory = $addPaymentHistory($this->fakePaymentHistory);
        $webhookCall = $this->webhookCallIndicatingPaymentStatusChangedTo($canceled);
        $paymentId = PaymentId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMolliePaymentStatusChanges::class](
            new MolliePaymentWasUpdated($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $canceled) {
            Event::assertNotDispatched(MolliePaymentStatusChangedToCanceled::class);
        } else {
            Event::assertDispatched(
                MolliePaymentStatusChangedToCanceled::class,
                new TruthTest(function (MolliePaymentStatusChangedToCanceled $event) use ($paymentId): void {
                    $this->assertSame($paymentId, $event->paymentId);
                }),
            );
        }
    }

    private function webhookCallIndicatingPaymentStatusChangedTo(string $status): WebhookCall
    {
        $payment = FakePayment::fake($this->app)->withStatus($status);

        return FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();
    }
}
