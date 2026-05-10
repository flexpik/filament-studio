<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Tools\Records\CreateRecordTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($this->collection, 'collection')->create([
        'tenant_id' => 1, 'column_name' => 'name', 'field_type' => 'text', 'eav_cast' => 'text', 'is_required' => true,
    ]);
});

it('creates a record', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Store->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, CreateRecordTool::class, [
        'collection_slug' => 'products',
        'data' => ['name' => 'Apple'],
    ])->assertSee('Apple');

    expect(StudioRecord::count())->toBe(1);
});

it('rejects without store action permission', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Index->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, CreateRecordTool::class, [
        'collection_slug' => 'products',
        'data' => ['name' => 'Apple'],
    ])->assertSee('STUDIO_UNAUTHORIZED');
});

it('returns STUDIO_VALIDATION_FAILED when required field missing', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Store->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, CreateRecordTool::class, [
        'collection_slug' => 'products',
        'data' => [],
    ])->assertSee('STUDIO_VALIDATION_FAILED');
});
