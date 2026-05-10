<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\ApiKeys;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetApiKeyTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_get_api_key';

    protected string $description = 'Fetch an API key (meta only) by id. The raw secret is never retrievable.';

    public function schema($schema): array
    {
        return ['id' => $schema->integer()->required()];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageApiKeys);
            $apiKey = app(StudioApiKeyContext::class)->require();
            $target = StudioApiKey::query()->forTenant($apiKey->tenant_id)->find((int) $request->get('id'));
            if ($target === null) {
                throw new StudioNotFoundException('api_key', (string) $request->get('id'));
            }

            return Response::json(['api_key' => (new McpSerializer)->apiKey($target)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
