<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class ServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__ . '/../config/laravel-mollie-webhooks.php';

    public function boot(Router $router): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([self::CONFIG_PATH => $this->app->configPath('laravel-mollie-webhooks.php')], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'laravel-mollie-webhooks');

        $this->app->bind(Config::class, IlluminateConfig::class);
    }
}
