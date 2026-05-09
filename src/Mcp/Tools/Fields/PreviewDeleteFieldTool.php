<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Fields;

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PreviewDeleteFieldTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_preview_delete_field';

    protected string $description = 'Preview consequences of deleting a field. Returns value_count, dependent saved filters, and a confirm_token required by studio_delete_field.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'column_name' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collectionSlug = (string) $request->get('collection_slug');
            $columnName = (string) $request->get('column_name');

            $c = StudioCollection::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $collectionSlug)
                ->first();

            if ($c === null) {
                throw new StudioNotFoundException('collection', $collectionSlug);
            }

            $field = StudioField::query()
                ->where('collection_id', $c->id)
                ->where('column_name', $columnName)
                ->first();

            if ($field === null) {
                throw new StudioNotFoundException('field', "{$collectionSlug}.{$columnName}");
            }

            $valueCount = StudioValue::query()
                ->where('field_id', $field->id)
                ->count();

            $dependentFilters = StudioSavedFilter::query()
                ->where('collection_id', $c->id)
                ->where('filter', 'like', '%"'.$columnName.'"%')
                ->get(['id', 'name'])
                ->toArray();

            $issuer = new ConfirmTokenIssuer(new ConfirmTokenStore);
            $token = $issuer->issue(
                'delete_field',
                ['collection_slug' => $collectionSlug, 'column_name' => $columnName],
                $apiKey->tenant_id,
            );

            return Response::json([
                'summary' => [
                    'value_count' => $valueCount,
                    'dependent_saved_filters' => $dependentFilters,
                ],
                'confirm_token' => $token['token'],
                'expires_at' => $token['expires_at'],
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
