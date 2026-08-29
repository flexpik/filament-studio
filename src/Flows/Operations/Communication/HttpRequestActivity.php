<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Communication;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Illuminate\Support\Facades\Http;

class HttpRequestActivity implements FlowOperation
{
    public const DANGEROUS = true;

    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $method = strtoupper((string) ($config['method'] ?? 'GET'));
        $url = (string) $config['url'];
        $headers = (array) ($config['headers'] ?? []);
        $body = $config['body'] ?? null;
        $timeout = (int) ($config['timeout_seconds'] ?? 30);
        $failOnError = (bool) ($config['fail_on_error'] ?? true);

        $request = Http::withHeaders($headers)->timeout($timeout);
        $response = match ($method) {
            'GET' => $request->get($url, is_array($body) ? $body : []),
            'DELETE' => $request->delete($url, is_array($body) ? $body : []),
            default => $request->send($method, $url, ['json' => $body]),
        };

        $contentType = (string) $response->header('Content-Type');
        $isJson = str_contains($contentType, 'json');
        $output = [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $isJson ? $response->json() : $response->body(),
        ];

        if ($failOnError && $response->failed()) {
            return OperationResult::withBranch('failure', $output);
        }

        return OperationResult::success($output);
    }
}
