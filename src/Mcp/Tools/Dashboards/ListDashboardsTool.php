<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Dashboards;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListDashboardsTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_list_dashboards';

    protected string $description = 'List dashboards for the current tenant with their panels.';

    public function schema($schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ReadSchema);
            $apiKey = app(StudioApiKeyContext::class)->require();

            $dashboards = StudioDashboard::query()
                ->forTenant($apiKey->tenant_id)
                ->ordered()
                ->with('panels')
                ->get();

            $serializer = new McpSerializer;

            return Response::json([
                'dashboards' => $dashboards->map(fn ($d) => $serializer->dashboard($d))->all(),
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
