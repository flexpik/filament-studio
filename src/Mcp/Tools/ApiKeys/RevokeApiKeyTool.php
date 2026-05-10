<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\ApiKeys;

use Flexpik\FilamentStudio\Mcp\Actions\ApiKeys\RevokeApiKey;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class RevokeApiKeyTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_revoke_api_key';

    protected string $description = 'Revoke an API key by setting is_active=false. Hard delete is out of scope for v1.';

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->required()];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageApiKeys);
            $apiKey = app(StudioApiKeyContext::class)->require();
            (new RevokeApiKey)((int) $request->get('id'), $apiKey->tenant_id);

            return Response::json(['revoked' => true, 'id' => (int) $request->get('id')]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
