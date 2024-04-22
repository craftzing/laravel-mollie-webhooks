<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factory;
use Spatie\WebhookClient\Models\WebhookCall;

/* @var Factory $factory */
$factory->define(WebhookCall::class, fn () => [
    'name' => 'mollie',
    'url' => fake()->url(),
    'payload' => [],
    'created_at' => CarbonImmutable::now(),
]);
