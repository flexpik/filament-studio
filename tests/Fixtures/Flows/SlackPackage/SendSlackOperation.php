<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows\SlackPackage;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Illuminate\Support\Facades\Http;

class SendSlackOperation implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $webhookUrl = (string) ($config['webhook_url'] ?? '');
        $message = $context->interpolate((string) ($config['message'] ?? ''));

        $response = Http::post($webhookUrl, ['text' => $message]);

        return OperationResult::success([
            'ok' => $response->successful(),
            'status' => $response->status(),
        ]);
    }
}
