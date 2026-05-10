<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Records;

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class QueryRecordsTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_query_records';

    protected string $description = 'Query records: filter (FilterGroup tree), sort, paginate, or aggregate. Mirrors the REST API + saved filter shape.';

    public function schema($schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'filter' => $schema->object(),
            'sort' => $schema->array(),
            'page' => $schema->integer(),
            'per_page' => $schema->integer(),
            'locale' => $schema->string(),
            'select' => $schema->array(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('collection_slug');

            $this->requireCollectionAction($slug, ApiAction::Index);

            $collection = StudioCollection::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->first();

            if ($collection === null) {
                throw new StudioNotFoundException('collection', $slug);
            }

            $maxPerPage = (int) config('filament-studio.mcp.limits.query_max_per_page', 100);
            $perPage = min((int) ($request->get('per_page') ?? 25), $maxPerPage);
            $page = max(1, (int) ($request->get('page') ?? 1));

            $builder = EavQueryBuilder::for($collection)
                ->tenant($apiKey->tenant_id)
                ->locale($request->get('locale'));

            if (is_array($request->get('select'))) {
                $builder->select($request->get('select'));
            }

            if (is_array($filter = $request->get('filter')) && ! empty($filter)) {
                $this->validateFilterDepth($filter);
                $builder->applyFilterTree(FilterGroup::fromArray($filter));
            }

            foreach ((array) $request->get('sort', []) as $sort) {
                if (isset($sort['field'])) {
                    $builder->orderBy($sort['field'], $sort['direction'] ?? 'asc');
                }
            }

            $paginator = $builder->paginate($perPage, $page);

            return Response::json([
                'data' => $paginator->items(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    protected function validateFilterDepth(array $filter, int $depth = 0): void
    {
        $max = (int) config('filament-studio.mcp.limits.query_max_filter_depth', 5);

        if ($depth > $max) {
            throw new InvalidArgumentException(
                "Filter nesting exceeds maximum allowed depth ({$max}).",
            );
        }

        foreach ($filter['rules'] ?? [] as $rule) {
            if (isset($rule['logic'])) {
                $this->validateFilterDepth($rule, $depth + 1);
            }
        }
    }
}
