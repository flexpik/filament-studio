<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\FieldOptions;

use Flexpik\FilamentStudio\Mcp\Actions\FieldOptions\SetFieldOptions;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SetFieldOptionsTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_set_field_options';

    protected string $description = 'Bulk-replace the option list for a select/multi_select/checkbox_list/radio field.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'column_name' => $schema->string()->required(),
            'options' => $schema->array()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collectionSlug = (string) $request->get('collection_slug');
            $columnName = (string) $request->get('column_name');

            $options = (new SetFieldOptions)(
                $collectionSlug,
                $columnName,
                (array) $request->get('options', []),
                $apiKey->tenant_id,
            );

            $serializer = new McpSerializer;

            return Response::json([
                'options' => array_map(fn ($o) => $serializer->fieldOption($o), $options),
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
