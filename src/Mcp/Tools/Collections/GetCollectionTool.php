<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Collections;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetCollectionTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_get_collection';

    protected string $description = 'Fetch a full collection definition (fields, options, settings) by slug.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ReadSchema);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');

            $c = StudioCollection::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->with('fields.options')
                ->first();

            if ($c === null) {
                throw new StudioNotFoundException('collection', $slug);
            }

            return Response::json(['collection' => (new McpSerializer)->collection($c)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
