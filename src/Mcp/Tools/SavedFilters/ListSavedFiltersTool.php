<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\SavedFilters;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListSavedFiltersTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_list_saved_filters';

    protected string $description = 'List saved filters for a collection in the current tenant.';

    public function schema($schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageFilters);
            $apiKey = app(StudioApiKeyContext::class)->require();

            $slug = (string) $request->get('collection_slug');

            $collection = StudioCollection::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->first();

            if ($collection === null) {
                throw new StudioNotFoundException('collection', $slug);
            }

            $filters = StudioSavedFilter::query()
                ->forTenant($apiKey->tenant_id)
                ->forCollection($collection->id)
                ->get();

            $serializer = new McpSerializer;

            return Response::json([
                'saved_filters' => $filters->map(fn ($f) => $serializer->savedFilter($f))->all(),
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
