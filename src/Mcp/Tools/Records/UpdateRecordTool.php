<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Records;

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Actions\Records\UpdateRecord;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateRecordTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_update_record';

    protected string $description = 'Update a record by UUID. Partial updates allowed via data map; only present fields are touched.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'uuid' => $schema->string()->required(),
            'data' => $schema->object()->required(),
            'locale' => $schema->string(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('collection_slug');

            $this->requireCollectionAction($slug, ApiAction::Update);

            $record = (new UpdateRecord)($request->all(), $apiKey->tenant_id);

            $collection = StudioCollection::query()->forTenant($apiKey->tenant_id)->where('slug', $slug)->first();
            $locale = $request->get('locale');
            $data = EavQueryBuilder::for($collection)->locale($locale)->getRecordData($record);

            return Response::json(['record' => (new McpSerializer)->record($record, $data, $locale)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
