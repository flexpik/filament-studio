<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Dashboards;

use Flexpik\FilamentStudio\Mcp\Actions\Dashboards\DeleteDashboard;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteDashboardTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_delete_dashboard';

    protected string $description = 'Delete a dashboard. Requires confirm_token from studio_preview_delete_dashboard.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'confirm_token' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageDashboards);
            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');

            (new ConfirmTokenStore)->consume(
                (string) $request->get('confirm_token'),
                'delete_dashboard',
                ['slug' => $slug],
                $apiKey->tenant_id,
            );

            (new DeleteDashboard)($slug, $apiKey->tenant_id);

            return Response::json(['deleted' => true, 'slug' => $slug]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}
