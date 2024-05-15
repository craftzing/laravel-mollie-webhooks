<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Http\Requests;

use Craftzing\Laravel\MollieWebhooks\Commands\ProcessMollieWebhook;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

use function json_encode;

final class HandleMollieWebhooksRequestTest extends IntegrationTestCase
{
    use RefreshDatabase;
    use WithFaker;

    private const URI = '/mollie/webhooks/handle';

    #[Before]
    public function registerWebhooksHandlerRoute(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->app[Router::class]->mollieWebhooks(self::URI);
        });
    }

    public static function invalidPayloads(): Generator
    {
        yield 'Empty payload' => [
            [],
        ];

        yield 'Payload without a Mollie object identifier' => [
            ['nonsense'],
        ];

        yield 'Payload with an invalid Mollie object identifier' => [
            ['id' => 'nonsense'],
        ];

        yield 'Payload with an unknown Mollie object identifier' => [
            ['id' => PaymentId::PREFIX . 'abcdefgh'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPayloads')]
    public function itCanHandleIncomingMollieWebhooksWithAnInvalidPayload(array $payload): void
    {
        $response = $this->post(self::URI, $payload);

        // As recommended by mollie, we should always return with a 200 OK
        // to prevent leaking information to (malicious) third parties.
        //
        // @see https://docs.mollie.com/guides/webhooks
        $response->assertOk();
        Bus::assertDispatched(ProcessMollieWebhook::class);
    }

    public static function validPayloads(): Generator
    {
        yield 'Payload with a Mollie payment identifier' => [
            ['id' => PaymentId::PREFIX . Str::random(8)],
        ];
    }

    #[Test]
    #[DataProvider('validPayloads')]
    public function itCanHandleIncomingMollieWebhooks(array $payload): void
    {
        $response = $this->post(self::URI, $payload);

        $response->assertOk();
        $this->assertDatabaseHas('webhook_calls', ['payload' => json_encode($payload)]);
        Bus::assertDispatched(ProcessMollieWebhook::class);
    }
}
