<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Panels;

use Flexpik\FilamentStudio\Mcp\Actions\Panels\DeletePanel;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeletePanelTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_delete_panel';

    protected string $description = 'Delete a panel by id. No confirm token required (panels are easily recreated).';

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->required()];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageDashboards);
            $apiKey = app(StudioApiKeyContext::class)->require();
            (new DeletePanel)((int) $request->get('id'), $apiKey->tenant_id);

            return Response::json(['deleted' => true, 'id' => (int) $request->get('id')]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
