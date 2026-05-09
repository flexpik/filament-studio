<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Records;

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Actions\Records\DeleteRecord;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteRecordTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_delete_record';

    protected string $description = 'Delete a record by UUID. Soft-deletes when the collection supports it; force=true hard-deletes (also drops related values).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'uuid' => $schema->string()->required(),
            'force' => $schema->boolean(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('collection_slug');

            $this->requireCollectionAction($slug, ApiAction::Destroy);

            (new DeleteRecord)(
                $slug,
                (string) $request->get('uuid'),
                $apiKey->tenant_id,
                (bool) $request->get('force', false),
            );

            return Response::json(['deleted' => true, 'uuid' => (string) $request->get('uuid')]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
