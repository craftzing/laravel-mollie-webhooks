<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Spatie\WebhookClient\Models\WebhookCall;

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(WebhookCall::class, fn () => [
    'name' => 'mollie',
    'payload' => [],
    'created_at' => CarbonImmutable::now(),
]);
