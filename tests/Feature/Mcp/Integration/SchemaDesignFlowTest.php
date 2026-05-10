<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKey;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

beforeEach(function () {
    config()->set('filament-studio.mcp.enabled', true);
    config()->set('filament-studio.mcp.http.enabled', true);
    config()->set('filament-studio.mcp.http.prefix', 'ai/studio');

    Route::middleware(['api', 'throttle:studio-mcp', ResolveStudioApiKey::class])
        ->group(function () {
            Mcp::web('/ai/studio', StudioMcpServer::class);
        });

    $plain = 'sk_test_schema_flow';
    $this->apiKey = StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'is_active' => true,
        'tenant_id' => 1,
        'permissions' => ['_studio' => ['manage_collections', 'read_schema']],
    ]);
    $this->headers = ['X-Api-Key' => $plain];
});

function jsonRpc(string $tool, array $arguments = []): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => uniqid('rpc_', true),
        'method' => 'tools/call',
        'params' => ['name' => $tool, 'arguments' => $arguments],
    ];
}

it('end-to-end: create collection, add field, preview delete, delete', function () {
    // Create collection
    $r = $this->postJson('/ai/studio', jsonRpc('studio_create_collection', ['name' => 'Products']), $this->headers);
    $r->assertOk();

    // Add field
    $r = $this->postJson('/ai/studio', jsonRpc('studio_create_field', [
        'collection_slug' => 'products',
        'column_name' => 'sku',
        'field_type' => 'text',
    ]), $this->headers);
    $r->assertOk();

    expect(StudioCollection::count())->toBe(1);

    // Preview delete
    $r = $this->postJson('/ai/studio', jsonRpc('studio_preview_delete_collection', ['slug' => 'products']), $this->headers);
    $r->assertOk();

    $text = data_get($r->json(), 'result.content.0.text', '');
    $body = json_decode($text, true);
    $confirmToken = $body['confirm_token'] ?? null;
    expect($confirmToken)->not->toBeNull();

    // Delete with token
    $r = $this->postJson('/ai/studio', jsonRpc('studio_delete_collection', [
        'slug' => 'products',
        'confirm_token' => $confirmToken,
    ]), $this->headers);
    $r->assertOk();

    expect(StudioCollection::count())->toBe(0);
});
