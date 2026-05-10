<?php

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Tests\SpatieTestCase;
use Flexpik\FilamentStudio\Tests\TestCase;
use Laravel\Mcp\Server\Testing\TestResponse;

uses(TestCase::class)->in('Unit', 'Feature');
uses(SpatieTestCase::class)->in('Integration');

/**
 * Set API key context and call a tool by class name.
 *
 * @param  array<string, mixed>  $input
 */
function mcpCallTool(StudioApiKey $apiKey, string $toolClass, array $input = []): TestResponse
{
    app(StudioApiKeyContext::class)->set($apiKey);

    return StudioMcpServer::tool($toolClass, $input);
}
