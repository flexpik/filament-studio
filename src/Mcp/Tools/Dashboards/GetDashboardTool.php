<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Dashboards;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetDashboardTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_get_dashboard';

    protected string $description = 'Get a dashboard by slug, including its panels.';

    public function schema(JsonSchema $schema): array
    {
        return ['slug' => $schema->string()->required()];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ReadSchema);
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');

            $dashboard = StudioDashboard::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->with('panels')
                ->first();

            if ($dashboard === null) {
                throw new StudioNotFoundException('dashboard', $slug);
            }

            return Response::json(['dashboard' => (new McpSerializer)->dashboard($dashboard)]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
