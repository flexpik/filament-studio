<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Tools\Records\UpdateRecordTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

it('updates a record', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($col, 'collection')->create([
        'tenant_id' => 1, 'column_name' => 'name', 'field_type' => 'text', 'eav_cast' => 'text',
    ]);
    $rec = EavQueryBuilder::for($col)->create(['name' => 'Old']);

    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Update->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, UpdateRecordTool::class, [
        'collection_slug' => 'products',
        'uuid' => $rec->uuid,
        'data' => ['name' => 'New'],
    ])->assertSee('New');

    $fresh = EavQueryBuilder::for($col)->getRecordData($rec->fresh());
    expect($fresh['name'])->toBe('New');
});
