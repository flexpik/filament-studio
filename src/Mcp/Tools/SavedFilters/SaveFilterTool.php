<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\SavedFilters;

use Flexpik\FilamentStudio\Mcp\Actions\SavedFilters\SaveSavedFilter;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SaveFilterTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_save_filter';

    protected string $description = 'Create or update a saved filter for a collection. Provide id to update an existing filter, omit to create a new one.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer(),
            'collection_slug' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'filter' => $schema->object()->required(),
            'is_shared' => $schema->boolean(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageFilters);
            $apiKey = app(StudioApiKeyContext::class)->require();

            $filter = (new SaveSavedFilter)($request->all(), $apiKey->tenant_id);

            return Response::json(['saved_filter' => (new McpSerializer)->savedFilter($filter)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
