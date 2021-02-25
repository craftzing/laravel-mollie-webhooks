<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Commands;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Events\MollieResourceStatusWasUpdated;
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

    public function webhookPayloads(): Generator
    {
        yield 'order resource id' => [
            fn () => $this->generateOrderId(),
            MollieOrderWasUpdated::class,
        ];

        yield 'payment resource id' => [
            fn () => $this->generatePaymentId(),
            MolliePaymentWasUpdated::class,
        ];
    }

    /**
     * @test
     * @dataProvider webhookPayloads
     */
    public function itCanHandleIncomingWebhooks(Callable $generatesResourceId, string $event): void
    {
        $resourceId = $generatesResourceId();
        $webhookCall = new WebhookCall([
            'payload' => [
                'id' => $resourceId->value(),
            ],
        ]);

        $this->handle(new ProcessMollieWebhook($webhookCall));

        Event::assertDispatched(
            $event,
            new TruthTest(function (MollieResourceStatusWasUpdated $event) use ($resourceId, $webhookCall): void {
                $this->assertSame($event->resourceId()->value(), $resourceId->value());
                $this->assertTrue($event->webhookCall()->is($webhookCall));
            }),
        );
    }
}
