<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Http\Requests\HandleMollieWebhooksRequest;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\WebhookCallOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\WebhookCallPaymentHistory;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class MollieWebhooksServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $router::macro('mollieWebhooks', function (string $uri) use ($router): void {
            $router->post($uri, HandleMollieWebhooksRequest::class);
        });
    }

    public function register(): void
    {
        $this->app->bind(OrderHistory::class, WebhookCallOrderHistory::class);
        $this->app->bind(PaymentHistory::class, WebhookCallPaymentHistory::class);
    }
}
