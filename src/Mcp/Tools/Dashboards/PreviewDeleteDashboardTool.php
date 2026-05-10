<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Dashboards;

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PreviewDeleteDashboardTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_preview_delete_dashboard';

    protected string $description = 'Preview deletion of a dashboard and obtain the confirm_token required by studio_delete_dashboard.';

    public function schema($schema): array
    {
        return ['slug' => $schema->string()->required()];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageDashboards);
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');

            $dashboard = StudioDashboard::query()
                ->forTenant($apiKey->tenant_id)
                ->where('slug', $slug)
                ->first();

            if ($dashboard === null) {
                throw new StudioNotFoundException('dashboard', $slug);
            }

            $panelCount = StudioPanel::query()
                ->where('dashboard_id', $dashboard->id)
                ->count();

            $issuer = new ConfirmTokenIssuer(new ConfirmTokenStore);
            $token = $issuer->issue('delete_dashboard', ['slug' => $slug], $apiKey->tenant_id);

            return Response::json([
                'summary' => [
                    'dashboard' => (new McpSerializer)->dashboard($dashboard),
                    'panel_count' => $panelCount,
                ],
                'confirm_token' => $token['token'],
                'expires_at' => $token['expires_at'],
            ]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
