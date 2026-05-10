<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Dashboards;

use Flexpik\FilamentStudio\Mcp\Actions\Dashboards\CreateDashboard;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateDashboardTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_create_dashboard';

    protected string $description = 'Create a dashboard. Required: name. Optional: slug, icon, color, auto_refresh_interval (seconds, min 5), sort_order.';

    public function schema($schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'slug' => $schema->string(),
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

            $dashboard = (new CreateDashboard)($request->all(), $apiKey->tenant_id);

            return Response::json(['dashboard' => (new McpSerializer)->dashboard($dashboard)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
