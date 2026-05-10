<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\ApiKeys;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListApiKeysTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_list_api_keys';

    protected string $description = 'List API keys for the current tenant. The raw secret is NEVER included.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageApiKeys);
            $apiKey = app(StudioApiKeyContext::class)->require();
            $keys = StudioApiKey::query()->forTenant($apiKey->tenant_id)->get();
            $serializer = new McpSerializer;

            return Response::json(['api_keys' => $keys->map(fn ($k) => $serializer->apiKey($k))->all()]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
