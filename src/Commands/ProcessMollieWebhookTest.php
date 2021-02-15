<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Commands;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Exceptions\UnexpectedWebhookPayload;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Exception;
use Generator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;

final class ProcessMollieWebhookTest extends IntegrationTestCase
{
    /**
     * @test
     */
    public function itShouldBeQueued(): void
    {
        $webhookCall = new WebhookCall([]);

        $this->assertInstanceOf(ShouldQueue::class, new ProcessMollieWebhook($webhookCall));
    }

    public function invalidWebhookPayloads(): Generator
    {
        yield 'Missing a Mollie object identifier' => [
            [],
            UnexpectedWebhookPayload::missingObjectIdentifier(),
        ];

        yield 'Mollie object identifier is invalid' => [
            ['id' => 'nonsense'],
            UnexpectedWebhookPayload::objectIdentifierCannotBeMappedToAMollieResource(),
        ];
    }

    /**
     * @test
     * @dataProvider invalidWebhookPayloads
     */
    public function itFailsWhenTheWebhookPayloadIsInvalid(array $payload, Exception $exception): void
    {
        $this->expectExceptionObject($exception);

        $webhookCall = new WebhookCall(compact('payload'));

        $this->handle(new ProcessMollieWebhook($webhookCall));
    }

    /**
     * @test
     */
    public function itCanHandleIncomingWebhooks(): void
    {
        $paymentId = $this->paymentId();
        $webhookCall = new WebhookCall([
            'payload' => [
                'id' => $paymentId->value(),
            ],
        ]);

        $this->handle(new ProcessMollieWebhook($webhookCall));

        Event::assertDispatched(
            MolliePaymentWasUpdated::class,
            new TruthTest(function (MolliePaymentWasUpdated $event) use ($paymentId, $webhookCall): void {
                $this->assertSame($event->paymentId->value(), $paymentId->value());
                $this->assertTrue($event->webhookCall->is($webhookCall));
            }),
        );
    }
}
