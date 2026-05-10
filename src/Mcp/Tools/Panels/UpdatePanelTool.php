<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Panels;

use Flexpik\FilamentStudio\Mcp\Actions\Panels\UpdatePanel;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdatePanelTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_update_panel';

    protected string $description = 'Update panel placement, grid, header, config, or sort_order. panel_type is immutable.';

    public function schema($schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'placement' => $schema->string(),
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
            $panel = (new UpdatePanel)($request->all(), $apiKey->tenant_id);

            return Response::json(['panel' => (new McpSerializer)->panel($panel)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
