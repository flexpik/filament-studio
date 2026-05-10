<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Fields;

use Flexpik\FilamentStudio\Mcp\Actions\Fields\CreateField;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateFieldTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_create_field';

    protected string $description = 'Add a field to a collection. Required: collection_slug, column_name, field_type. Optional: label, eav_cast, settings, options[].';

    public function schema($schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'column_name' => $schema->string()->required(),
            'field_type' => $schema->string()->required(),
            'label' => $schema->string(),
            'eav_cast' => $schema->string(),
            'settings' => $schema->array(),
            'options' => $schema->array(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collectionSlug = (string) $request->get('collection_slug');
            $input = array_diff_key($request->all(), ['collection_slug' => true]);

            $field = (new CreateField)($collectionSlug, $input, $apiKey->tenant_id);

            return Response::json(['field' => (new McpSerializer)->field($field)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
