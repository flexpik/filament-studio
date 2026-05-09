<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp;

use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKeyFromEnv;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Filament Studio')]
#[Version('0.1.0')]
#[Instructions(
    'Filament Studio is a dynamic data model manager built on Filament v5 with EAV storage. '.
    'Through this MCP server you can manage collections (data models), fields (columns), records (rows), '.
    'dashboards and panels (read views), saved filters, and API keys. '.
    'Read the studio://info, studio://field-types, studio://panel-types, and studio://operators resources first '.
    'to discover the catalog of capabilities before using tools.'
)]
class StudioMcpServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [];

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
