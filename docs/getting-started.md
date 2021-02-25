Getting started
===

This section will guide you through all necessary and optional installation and configuration steps.

> 💡 Found an issue or is this section missing anything? Feel free to open a 
> [PR](https://github.com/craftzing/laravel-mollie-webhooks/compare) or 
> [issue](https://github.com/craftzing/laravel-mollie-webhooks/issues/new).

## ⚒️ Requirements

This package requires:
- [PHP](https://www.php.net/supported-versions.php) 7.4 or 8

Some features may have additional requirements. These will be listed in the according section of the documentation.

## 🧙 Installation

You can install this package using [Composer](https://getcomposer.org) by running the following command:
```bash
composer require craftzing/laravel-mollie-webhooks
```

We're using [Laravel's package discovery](https://laravel.com/docs/8.x/packages#package-discovery) to automatically
register the service provider, so you don't have to register it yourself.

You can publish the package config file by running the command below, but it's not mandatory:
```bash
php artisan vendor:publish --provider="Craftzing\Laravel\MollieWebhooks\ServiceProvider" --tag="config"
```

### Routes
The package provides a macro to tie the route to the `HandleMollieWebhooksRequest` action request. 
You can add it in the boot method of your `RouteServiceProvider` or directly in your route files.
```php
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function (Router $router) {
            $router->mollieWebhooks('/mollie/webhook');
        }
    }
}
```

## ⚙️ Configuration

If the package requires any configuration, this is where to document it. If it doesn't, remove this section.

---

[Usage ⏩](usage.md)
