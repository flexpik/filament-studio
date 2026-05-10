<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Dashboards;

use Flexpik\FilamentStudio\Mcp\Actions\Dashboards\UpdateDashboard;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateDashboardTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_update_dashboard';

    protected string $description = 'Update dashboard meta (name, icon, color, auto_refresh_interval, sort_order). Panels are managed by panel tools.';

    public function schema($schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'name' => $schema->string(),
            'icon' => $schema->string(),
            'color' => $schema->string(),
            'auto_refresh_interval' => $schema->integer(),
            'sort_order' => $schema->integer(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageDashboards);
            $apiKey = app(StudioApiKeyContext::class)->require();

            $dashboard = (new UpdateDashboard)($request->all(), $apiKey->tenant_id);

            return Response::json(['dashboard' => (new McpSerializer)->dashboard($dashboard)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
