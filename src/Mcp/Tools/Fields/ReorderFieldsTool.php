<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Fields;

use Flexpik\FilamentStudio\Mcp\Actions\Fields\ReorderFields;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReorderFieldsTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_reorder_fields';

    protected string $description = 'Reorder fields within a collection. Provide the complete ordered list of column_names.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'column_names' => $schema->array()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collectionSlug = (string) $request->get('collection_slug');
            $columnNames = (array) $request->get('column_names', []);

            (new ReorderFields)($collectionSlug, $columnNames, $apiKey->tenant_id);

            return Response::json(['reordered' => true, 'collection_slug' => $collectionSlug]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
