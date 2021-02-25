Resource specific events
===

When Mollie calls a webhook, it only includes a resource ID. This is great from a security point of view, but it comes 
with the cost of requiring developers to fetch the resource and figure out what changed on it themselves. Resource 
specific events aim at limiting the amount of manual labour you have to do when processing a Mollie webhook call.

> 💡 Found an issue or is this section missing anything? Feel free to open a
> [PR](https://github.com/craftzing/laravel-mollie-webhooks/compare) or
> [issue](https://github.com/craftzing/laravel-mollie-webhooks/issues/new).

## 💶 Payments

When Mollie calls the webhook with a payment ID, we'll dispatch a generic event called 
`Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated`. You can listen for this event and determine what has
changed on the payment yourself, but we also offer a couple of optional subscribers that can do this for you.

### Subscribing to Payment Status changes

To use this subscriber, you can register it in your app's `EventServiceProvider`:
```php
protected $subscribe = [
    \Craftzing\Laravel\MollieWebhooks\Subscribers\SubscribeToMolliePaymentStatusChanges::class,
];
```

When registered, this subscriber will fire whenever `Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated` 
gets dispatched. It fetches the payment resource from the Mollie API and dispatches a more specific event based on the 
payment status. It does so cleverly by only dispatching the event when the status actually changed compared to the 
latest known status in your system. For an in-depth dive into when we fire which event, have a look at the 
[Updated Payment EPC](#updated-payment-epc "Updated Payment Event-driven Process Chain").

> 💡 Mollie only calls the webhook when a payment reaches one of the following statuses:
> - `paid`
> - `expired`
> - `failed`
> - `canceled`
> 
> This means we emit specific payment status change events for these statuses only. We do not emit events for `open`,
> `pending` or `authorized`.

### Subscribing to Payment Refunds

To use this subscriber, you can register it in your app's `EventServiceProvider`:
```php
protected $subscribe = [
    \Craftzing\Laravel\MollieWebhooks\Subscribers\SubscribeToMolliePaymentRefunds::class,
];
```

When registered, this subscriber will fire whenever `Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated`
gets dispatched. It fetches the payment resource from the Mollie API, loops through all refunds associated with it and
dispatches a `Craftzing\Laravel\MollieWebhooks\Events\MollieRefundWasTransferred` event for each transferred refund. It 
does so cleverly by only dispatching the event when the refund status is not `refunded` in your system. For an in-depth 
dive into when we fire which event, have a look at the [Updated Payment EPC](#updated-payment-epc "Updated Payment Event-driven Process Chain").

> 💡 Mollie only calls the webhook when a refund associated with the payment reaches the `refunded` status. For that 
> reason, we only emit payment refund events for that exact status.

### Updated payment EPC

![Updated Payment EPC](/art/updated-payment-epc.png)

### Payment history

In order to compare fresh data retrieved from the Mollie API with data in your app, the subscriber uses a
`PaymentHistory`.

This package comes with a `Craftzing\Laravel\MollieWebhooks\Payments\WebhookCallPaymentHistory` implementation out of 
the box. This implementation does 2 things:
1. It compares the freshly retrieved data with the latest data that could be found in a previous webhook call 
   for that same resource.
2. It appends the freshly retrieved data to the payload of the ongoing webhook call whenever it differs from 
   the latest data in the webhook call history.

If your application keeps track of Mollie data (for example by saving it to one of your database resources), you may 
want to use your own implementation instead of relying on the webhook call history. You can do so by creating your own 
implementation of `Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory` and rebinding it to the interface in the 
Laravel IoC container in one of your service providers:
```php
use App\Payments\YourPaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->extend(PaymentHistory::class, fn () => new YourPaymentHistory());
    }
}
```

> 💡 While the `WebhookCallPaymentHistory` implementation actually writes the updated data to the `webhook_calls` 
> database table right away (as we need that data available immediately), we highly recommend keeping your custom 
> `PaymentHistory` implementations read-only. You should update the data in your system by registering listeners for 
> the resource specific events.

## Orders

When Mollie calls the webhook with an order ID, we'll dispatch a generic event called
`Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated`. You can listen for this event and determine what has
changed on the payment yourself, but we also offer a couple of optional subscribers that can do this for you.

### Subscribing to Order Status changes

To use this subscriber, you can register it in your app's `EventServiceProvider`:
```php
protected $subscribe = [
    \Craftzing\Laravel\MollieWebhooks\Subscribers\SubscribeToMollieOrderStatusChanges::class,
];
```

When registered, this subscriber will fire whenever `Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated`
gets dispatched. It fetches the order resource from the Mollie API and dispatches a more specific event based on the
order status. It does so cleverly by only dispatching the event when the status actually changed compared to the
latest known status in your system. For an in-depth dive into when we fire which event, have a look at the
[Updated Order EPC](#updated-order-epc "Updated Order Event-driven Process Chain").

> 💡 Mollie only calls the webhook when an order reaches one of the following statuses:
> - `authorized`
> - `canceled`
> - `completed`
> - `expired`
> - `paid`
>
> This means we emit specific order status change events for these statuses only. We do not emit events for `created`,
> `pending` or `shipping`.

### Subscribing to Order Refunds

To use this subscriber, you can register it in your app's `EventServiceProvider`:
```php
protected $subscribe = [
    \Craftzing\Laravel\MollieWebhooks\Subscribers\SubscribeToMollieOrderRefunds::class,
];
```

When registered, this subscriber will fire whenever `Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated`
gets dispatched. It fetches the order resource from the Mollie API, loops through all refunds associated with it and
dispatches a `Craftzing\Laravel\MollieWebhooks\Events\MollieRefundWasTransferred` event for each transferred refund. It
does so cleverly by only dispatching the event when the refund status is not `refunded` in your system. For an in-depth
dive into when we fire which event, have a look at the [Updated Order EPC](#updated-order-epc "Updated Order Event-driven Process Chain").

> 💡 Mollie only calls the webhook when a refund associated with the order reaches the `refunded` status. For that
> reason, we only emit payment refund events for that exact status.

### Updated Order EPC

`<schema placeholder>`

### Order history

In order to compare fresh data retrieved from the Mollie API with data in your app, the subscriber uses an
`OrderHistory`.

This package comes with a `Craftzing\Laravel\MollieWebhooks\Orders\WebhookCallOrderHistory` implementation out of
the box. This implementation does 2 things:
1. It compares the freshly retrieved data with the latest data that could be found in a previous webhook call
   for that same resource.
2. It appends the freshly retrieved data to the payload of the ongoing webhook call whenever it differs from
   the latest data in the webhook call history.

If your application keeps track of Mollie data (for example by saving it to one of your database resources), you may
want to use your own implementation instead of relying on the webhook call history. You can do so by creating your own
implementation of `Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory` and rebinding it to the interface in the
Laravel IoC container in one of your service providers:
```php
use App\Orders\YourOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->extend(OrderHistory::class, fn () => new YourOrderHistory());
    }
}
```

> 💡 While the `WebhookCallOrderHistory` implementation actually writes the updated data to the `webhook_calls`
> database table right away (as we need that data available immediately), we highly recommend keeping your custom
> `OrderHistory` implementations read-only. You should update the data in your system by registering listeners for
> the resource specific events.