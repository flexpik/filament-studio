<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Fields;

use Flexpik\FilamentStudio\Mcp\Actions\Fields\UpdateField;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateFieldTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_update_field';

    protected string $description = 'Update field metadata. collection_slug and column_name identify the field; all other properties are optional.';

    public function schema($schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'column_name' => $schema->string()->required(),
            'label' => $schema->string(),
            'field_type' => $schema->string(),
            'eav_cast' => $schema->string(),
            'settings' => $schema->array(),
            'is_required' => $schema->boolean(),
            'is_unique' => $schema->boolean(),
            'is_filterable' => $schema->boolean(),
            'is_translatable' => $schema->boolean(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collectionSlug = (string) $request->get('collection_slug');
            $columnName = (string) $request->get('column_name');
            $input = array_diff_key($request->all(), ['collection_slug' => true, 'column_name' => true]);

            $field = (new UpdateField)($collectionSlug, $columnName, $input, $apiKey->tenant_id);

            return Response::json(['field' => (new McpSerializer)->field($field)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
