Resource specific events
===

When Mollie calls a webhook, it only includes a resource ID. This is great from a security point of view, but it comes 
with the cost of requiring developers to fetch the resource and figure out what changed on it themselves. Resource 
specific event aim at limiting the amount of manual labour you have to do when processing a Mollie webhook call.

> 💡 Found an issue or is this section missing anything? Feel free to open a
> [PR](https://github.com/craftzing/laravel-mollie-webhooks/compare) or
> [issue](https://github.com/craftzing/laravel-mollie-webhooks/issues/new).

## 💶 Payments

When Mollie calls the webhook with a Payment ID, we'll dispatch a generic event called 
`Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated`. You can listen for this event and determine what has
changed on the Payment yourself, but we also offer a couple of optional subscribers for that event that can do this for
you. 

### Subscribing to Payment Status changes

To use this subscriber, you can register it in your app's `EventServiceProvider`:
```php
protected $subscribe = [
    \Craftzing\Laravel\MollieWebhooks\Subscribers\SubscribeToMolliePaymentStatusChanges::class,
];
```

When registered, this subscriber will fire when the `Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated` 
gets dispatched. It fetches the Payment resource from the Mollie API and dispatches a more specific event based on the 
payment status. It does so cleverly by only dispatching the event when the status actually changed compared to the 
latest known status in your system:
![Updated Payment EPC](/art/updated-payment-epc.png)

#### Payment history

In order to determine if a payment status has changed, the subscriber uses a `PaymentHistory`.

This package comes with a `Craftzing\Laravel\MollieWebhooks\Payments\WebhookCallPaymentHistory` implementation out of 
the box. This implementation does 2 things:
1. It appends the freshly retrieved payment status to the payload of the ongoing webhook call.
2. It compares the freshly retrieved payment status with the latest one that could be found in a previous webhook call.

If your application keeps track of the Payment Status (for example by saving it to one of your database resources), you
may want to use your own implementation to compare the freshly retrieved status with the latest known status in your 
system. You can do so by creating your own implementation of `Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory`
and rebinding it to the interface in the Laravel IoC container in one of your service providers:
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
