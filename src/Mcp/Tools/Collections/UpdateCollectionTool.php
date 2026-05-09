<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Collections;

use Flexpik\FilamentStudio\Mcp\Actions\Collections\UpdateCollection;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateCollectionTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_update_collection';

    protected string $description = 'Update collection meta (name, label, icon, description, flags). Slug is immutable.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'name' => $schema->string(),
            'label' => $schema->string(),
            'icon' => $schema->string(),
            'description' => $schema->string(),
            'is_hidden' => $schema->boolean(),
            'api_enabled' => $schema->boolean(),
            'enable_versioning' => $schema->boolean(),
            'enable_soft_deletes' => $schema->boolean(),
            'archive_field' => $schema->string(),
            'supported_locales' => $schema->array(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');
            $input = array_diff_key($request->all(), ['slug' => true]);

            $collection = (new UpdateCollection)($slug, $input, $apiKey->tenant_id);

            return Response::json(['collection' => (new McpSerializer)->collection($collection)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
