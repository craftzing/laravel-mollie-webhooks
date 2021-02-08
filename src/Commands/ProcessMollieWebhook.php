<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Commands;

use Illuminate\Contracts\Events\Dispatcher;
use Spatie\WebhookClient\ProcessWebhookJob;

final class ProcessMollieWebhook extends ProcessWebhookJob
{
    public function handle(Dispatcher $events): void
    {
        
    }
}
