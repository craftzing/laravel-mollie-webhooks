<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Http\Requests\HandleMollieWebhooksRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class MollieWebhooksServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__ . '/../config/mollie-webhooks.php';

    public function boot(Router $router): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([self::CONFIG_PATH => $this->app->configPath('mollie-webhooks.php')], 'config');
        }

        $router::macro('mollieWebhooks', function (string $uri) use ($router): void {
            $router->post($uri, HandleMollieWebhooksRequest::class);
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'mollie-webhooks');

        $this->app->bind(Config::class, IlluminateConfig::class);
    }
}
