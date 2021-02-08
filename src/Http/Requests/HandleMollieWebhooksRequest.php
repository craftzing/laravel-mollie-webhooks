<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Http\Requests;

use Craftzing\Laravel\MollieWebhooks\Commands\ProcessMollieWebhook;
use Craftzing\Laravel\MollieWebhooks\Config;
use Craftzing\Laravel\MollieWebhooks\Http\MollieSignatureValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

final class HandleMollieWebhooksRequest extends Controller
{
    public function __invoke(Request $request, Config $config): JsonResponse
    {
        $webhookConfig = new WebhookConfig([
            'name' => MollieSignatureValidator::NAME,
            'signature_validator' => MollieSignatureValidator::class,
            'webhook_profile' => ProcessEverythingWebhookProfile::class,
            'webhook_model' => WebhookCall::class,
            'process_webhook_job' => ProcessMollieWebhook::class,
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
