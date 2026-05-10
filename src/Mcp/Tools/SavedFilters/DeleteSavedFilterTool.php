<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\SavedFilters;

use Flexpik\FilamentStudio\Mcp\Actions\SavedFilters\DeleteSavedFilter;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteSavedFilterTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_delete_saved_filter';

    protected string $description = 'Delete a saved filter by id.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageFilters);
            $apiKey = app(StudioApiKeyContext::class)->require();

            $id = (int) $request->get('id');

            (new DeleteSavedFilter)($id, $apiKey->tenant_id);

            return Response::json(['deleted' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
