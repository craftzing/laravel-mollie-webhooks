<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Http\Requests\HandleMollieWebhooksRequest;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

final class MollieWebhooksServiceProviderTest extends IntegrationTestCase
{
    private const URI = 'mollie/webhooks/handle';

    /**
     * @test
     */
    public function itExtendsTheRouterToEnableRegisteringARouteToHandleIncomingWebhooks(): void
    {
        $router = $this->app[Router::class];

        $router->mollieWebhooks(self::URI);

        $this->assertInstanceOf(
            Route::class,
            $route = $router->getRoutes()->getByAction(HandleMollieWebhooksRequest::class),
        );
        $this->assertSame(self::URI, $route->uri);
    }
}
