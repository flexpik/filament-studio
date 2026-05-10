<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Panels;

use Flexpik\FilamentStudio\Mcp\Actions\Panels\ReorderPanels;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReorderPanelsTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_reorder_panels';

    protected string $description = 'Reorder panels within a dashboard. panel_ids[] in desired order; both grid_order and sort_order are updated to match.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'dashboard_slug' => $schema->string()->required(),
            'panel_ids' => $schema->array()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageDashboards);
            $apiKey = app(StudioApiKeyContext::class)->require();
            $count = (new ReorderPanels)($request->all(), $apiKey->tenant_id);

            return Response::json(['reordered' => true, 'count' => $count]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
