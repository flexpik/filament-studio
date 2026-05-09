<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Records;

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRecordTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_get_record';

    protected string $description = 'Fetch a single record by UUID. Optional all_locales=true returns every locale variant.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'uuid' => $schema->string()->required(),
            'all_locales' => $schema->boolean(),
            'locale' => $schema->string(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('collection_slug');

            $this->requireCollectionAction($slug, ApiAction::Show);

            $collection = StudioCollection::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->first();

            if ($collection === null) {
                throw new StudioNotFoundException('collection', $slug);
            }

            $record = StudioRecord::query()
                ->where('collection_id', $collection->id)
                ->where('uuid', (string) $request->get('uuid'))
                ->first();

            if ($record === null) {
                throw new StudioNotFoundException('record', (string) $request->get('uuid'));
            }

            if ($request->get('all_locales') === true) {
                $data = EavQueryBuilder::for($collection)->getAllLocaleData($record);

                return Response::json([
                    'record' => (new McpSerializer)->record($record, $data, null),
                ]);
            }

            $locale = $request->get('locale');
            $data = EavQueryBuilder::for($collection)->locale($locale)->getRecordData($record);

            return Response::json([
                'record' => (new McpSerializer)->record($record, $data, $locale),
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
