<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Panels;

use Flexpik\FilamentStudio\Mcp\Actions\Panels\CreatePanel;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreatePanelTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_create_panel';

    protected string $description = 'Create a panel on a dashboard or collection. Required: dashboard_slug, panel_type, placement. See studio://panel-types/{key} for the per-type config schema.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'dashboard_slug' => $schema->string()->required(),
            'panel_type' => $schema->string()->required(),
            'placement' => $schema->string()->required(),
            'context_collection_id' => $schema->integer(),
            'grid' => $schema->object(),
            'header' => $schema->object(),
            'config' => $schema->object(),
            'sort_order' => $schema->integer(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageDashboards);
            $apiKey = app(StudioApiKeyContext::class)->require();

            $panel = (new CreatePanel)($request->all(), $apiKey->tenant_id);

            return Response::json(['panel' => (new McpSerializer)->panel($panel)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
