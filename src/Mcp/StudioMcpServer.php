<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp;

use Flexpik\FilamentStudio\Mcp\Resources\FieldTypeCatalogResource;
use Flexpik\FilamentStudio\Mcp\Resources\FieldTypeDetailResource;
use Flexpik\FilamentStudio\Mcp\Resources\OperatorCatalogResource;
use Flexpik\FilamentStudio\Mcp\Resources\PanelTypeCatalogResource;
use Flexpik\FilamentStudio\Mcp\Resources\PanelTypeDetailResource;
use Flexpik\FilamentStudio\Mcp\Resources\ServerInfoResource;
use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKeyFromEnv;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\CreateCollectionTool;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\DeleteCollectionTool;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\GetCollectionTool;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\ListCollectionsTool;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\PreviewDeleteCollectionTool;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\UpdateCollectionTool;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\CreateDashboardTool;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\DeleteDashboardTool;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\GetDashboardTool;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\ListDashboardsTool;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\PreviewDeleteDashboardTool;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\UpdateDashboardTool;
use Flexpik\FilamentStudio\Mcp\Tools\FieldOptions\SetFieldOptionsTool;
use Flexpik\FilamentStudio\Mcp\Tools\Fields\CreateFieldTool;
use Flexpik\FilamentStudio\Mcp\Tools\Fields\DeleteFieldTool;
use Flexpik\FilamentStudio\Mcp\Tools\Fields\PreviewDeleteFieldTool;
use Flexpik\FilamentStudio\Mcp\Tools\Fields\ReorderFieldsTool;
use Flexpik\FilamentStudio\Mcp\Tools\Fields\UpdateFieldTool;
use Flexpik\FilamentStudio\Mcp\Tools\Panels\CreatePanelTool;
use Flexpik\FilamentStudio\Mcp\Tools\Records\CreateRecordTool;
use Flexpik\FilamentStudio\Mcp\Tools\Records\DeleteRecordTool;
use Flexpik\FilamentStudio\Mcp\Tools\Records\GetRecordTool;
use Flexpik\FilamentStudio\Mcp\Tools\Records\QueryRecordsTool;
use Flexpik\FilamentStudio\Mcp\Tools\Records\UpdateRecordTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

class StudioMcpServer extends Server
{
    protected string $name = 'Filament Studio';

    protected string $version = '0.1.0';

    protected string $instructions = 'Filament Studio is a dynamic data model manager built on Filament v5 with EAV storage. '.
        'Through this MCP server you can manage collections (data models), fields (columns), records (rows), '.
        'dashboards and panels (read views), saved filters, and API keys. '.
        'Read the studio://info, studio://field-types, studio://panel-types, and studio://operators resources first '.
        'to discover the catalog of capabilities before using tools.';

    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListCollectionsTool::class,
        GetCollectionTool::class,
        CreateCollectionTool::class,
        UpdateCollectionTool::class,
        PreviewDeleteCollectionTool::class,
        DeleteCollectionTool::class,
        CreateFieldTool::class,
        UpdateFieldTool::class,
        PreviewDeleteFieldTool::class,
        DeleteFieldTool::class,
        ReorderFieldsTool::class,
        SetFieldOptionsTool::class,
        QueryRecordsTool::class,
        GetRecordTool::class,
        CreateRecordTool::class,
        UpdateRecordTool::class,
        DeleteRecordTool::class,
        ListDashboardsTool::class,
        GetDashboardTool::class,
        CreateDashboardTool::class,
        UpdateDashboardTool::class,
        PreviewDeleteDashboardTool::class,
        DeleteDashboardTool::class,
        CreatePanelTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        ServerInfoResource::class,
        FieldTypeCatalogResource::class,
        FieldTypeDetailResource::class,
        PanelTypeCatalogResource::class,
        PanelTypeDetailResource::class,
        OperatorCatalogResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [];

    public function boot(): void
    {
        parent::boot();

        if ($this->runningOverStdio()) {
            app(ResolveStudioApiKeyFromEnv::class)->resolve();
        }
    }

    protected function runningOverStdio(): bool
    {
        return app()->runningInConsole()
            && in_array('mcp:start', $_SERVER['argv'] ?? [], true);
    }
}
