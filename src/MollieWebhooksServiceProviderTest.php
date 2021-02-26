<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Http\Requests\HandleMollieWebhooksRequest;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\WebhookCallOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\WebhookCallPaymentHistory;
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

    /**
     * @test
     */
    public function itBindsADefaultImplementationForThePaymentHistory(): void
    {
        $paymentHistory = $this->app[PaymentHistory::class];

        $this->assertInstanceOf(WebhookCallPaymentHistory::class, $paymentHistory);
    }

    /**
     * @test
     */
    public function itBindsADefaultImplementationForTheOrderHistory(): void
    {
        $orderHistory = $this->app[OrderHistory::class];

        $this->assertInstanceOf(WebhookCallOrderHistory::class, $orderHistory);
    }
}
