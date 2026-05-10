<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Collections;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListCollectionsTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_list_collections';

    protected string $description = 'List collections for the current tenant. Supports filters: is_singleton, is_hidden, api_enabled, name_search; pagination via page/per_page (max 100).';

    public function schema($schema): array
    {
        return [
            'is_singleton' => $schema->boolean(),
            'is_hidden' => $schema->boolean(),
            'api_enabled' => $schema->boolean(),
            'name_search' => $schema->string(),
            'page' => $schema->integer(),
            'per_page' => $schema->integer(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ReadSchema);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $perPage = min((int) $request->get('per_page', 25), 100);
            $page = max((int) $request->get('page', 1), 1);

            $query = StudioCollection::query()->forTenant($apiKey->tenant_id);

            foreach (['is_singleton', 'is_hidden', 'api_enabled'] as $flag) {
                if ($request->has($flag)) {
                    $query->where($flag, (bool) $request->get($flag));
                }
            }
            if ($search = $request->get('name_search')) {
                $query->where('name', 'like', '%'.$search.'%');
            }

            $paginator = $query->orderBy('id')->paginate(perPage: $perPage, page: $page);
            $serializer = new McpSerializer;

            return Response::json([
                'collections' => collect($paginator->items())
                    ->map(fn ($c) => $serializer->collection($c))
                    ->all(),
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
