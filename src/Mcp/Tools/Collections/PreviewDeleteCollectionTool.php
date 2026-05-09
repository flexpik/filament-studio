<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Collections;

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PreviewDeleteCollectionTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_preview_delete_collection';

    protected string $description = 'Preview the impact of deleting a collection and obtain a confirm_token required by studio_delete_collection.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');

            $c = StudioCollection::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->with('fields')
                ->first();

            if ($c === null) {
                throw new StudioNotFoundException('collection', $slug);
            }

            $recordCount = StudioRecord::query()
                ->where('collection_id', $c->id)
                ->count();

            $savedFilters = StudioSavedFilter::query()
                ->where('collection_id', $c->id)
                ->get(['id', 'name'])
                ->toArray();

            $panels = StudioPanel::query()
                ->where('context_collection_id', $c->id)
                ->get(['id', 'panel_type'])
                ->toArray();

            $warnings = [];
            if ($recordCount > 0 && ! $c->enable_soft_deletes) {
                $warnings[] = "{$recordCount} records will be permanently deleted (no soft-delete enabled).";
            }

            $issuer = new ConfirmTokenIssuer(new ConfirmTokenStore);
            $token = $issuer->issue('delete_collection', ['slug' => $slug], $apiKey->tenant_id);
            $serializer = new McpSerializer;

            return Response::json([
                'summary' => [
                    'collection' => array_merge(
                        $serializer->collection($c),
                        ['record_count' => $recordCount]
                    ),
                    'fields' => $c->fields->map(fn ($f) => $serializer->field($f))->all(),
                    'dependents' => [
                        'panels' => $panels,
                        'saved_filters' => $savedFilters,
                    ],
                    'warnings' => $warnings,
                ],
                'confirm_token' => $token['token'],
                'expires_at' => $token['expires_at'],
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
