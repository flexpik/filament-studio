<?php

use Flexpik\FilamentStudio\Contracts\Flows\DataChain;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Tests\SpatieTestCase;
use Flexpik\FilamentStudio\Tests\TestCase;
use Laravel\Mcp\Server\Testing\TestResponse;

uses(TestCase::class)->in('Unit', 'Feature', 'Architecture');
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

function makeOperationContext(array $trigger = [], array $outputs = [], array $last = [], array $config = [], string $tenantId = 'test-tenant', array $accountability = []): OperationContext
{
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create(
        $accountability ? ['accountability' => $accountability] : [],
    );
    $chain = new DataChain(
        trigger: $trigger,
        outputs: $outputs,
        last: $last ?: (empty($outputs) ? [] : end($outputs)),
    );

    return new OperationContext(
        flow: $flow,
        run: $run,
        dataChain: $chain,
        config: $config,
        tenantId: $tenantId,
    );
}
