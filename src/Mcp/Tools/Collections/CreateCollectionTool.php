<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Collections;

use Flexpik\FilamentStudio\Mcp\Actions\Collections\CreateCollection;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateCollectionTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_create_collection';

    protected string $description = 'Create a collection. Optional: slug, label, icon, description, is_singleton, api_enabled, enable_versioning, enable_soft_deletes, archive_field, supported_locales, fields[].';

    public function schema($schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'slug' => $schema->string(),
            'label' => $schema->string(),
            'icon' => $schema->string(),
            'description' => $schema->string(),
            'is_singleton' => $schema->boolean(),
            'is_hidden' => $schema->boolean(),
            'api_enabled' => $schema->boolean(),
            'enable_versioning' => $schema->boolean(),
            'enable_soft_deletes' => $schema->boolean(),
            'archive_field' => $schema->string(),
            'supported_locales' => $schema->array(),
            'fields' => $schema->array(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collection = (new CreateCollection)($request->all(), $apiKey->tenant_id);

            return Response::json(['collection' => (new McpSerializer)->collection($collection)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
