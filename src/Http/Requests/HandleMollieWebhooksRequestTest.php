<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Http\Requests;

use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Routing\Router;

final class HandleMollieWebhooksRequestTest extends IntegrationTestCase
{
    use RefreshDatabase;
    use WithFaker;

    private const URI = '/mollie/webhooks/handle';

    /**
     * @before
     */
    public function registerWebhooksHandlerRoute(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->app[Router::class]->mollieWebhooks(self::URI);
        });
    }
}
