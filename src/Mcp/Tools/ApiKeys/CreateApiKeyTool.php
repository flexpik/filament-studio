<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\ApiKeys;

use Flexpik\FilamentStudio\Mcp\Actions\ApiKeys\CreateApiKey;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateApiKeyTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_create_api_key';

    protected string $description = 'PRIVILEGED: create a new API key. The plaintext secret is returned ONCE in this response — store it immediately, it cannot be retrieved later.';

    public function schema($schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'permissions' => $schema->object(),
            'expires_at' => $schema->string(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageApiKeys);
            $apiKey = app(StudioApiKeyContext::class)->require();
            ['key' => $created, 'secret' => $secret] = (new CreateApiKey)($request->all(), $apiKey->tenant_id);

            return Response::json([
                'api_key' => (new McpSerializer)->apiKey($created),
                'secret' => $secret,
                'warning' => 'Store this secret immediately. It cannot be retrieved again.',
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
