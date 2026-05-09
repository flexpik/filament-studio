<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Tools\Records\DeleteRecordTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

it('deletes a record (soft when collection supports it)', function () {
    $col = StudioCollection::factory()->create([
        'slug' => 'products', 'tenant_id' => 1, 'enable_soft_deletes' => true,
    ]);
    StudioField::factory()->for($col, 'collection')->create([
        'tenant_id' => 1, 'column_name' => 'name', 'field_type' => 'text', 'eav_cast' => 'text',
    ]);
    $rec = EavQueryBuilder::for($col)->create(['name' => 'Doomed']);

    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Destroy->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, DeleteRecordTool::class, [
        'collection_slug' => 'products',
        'uuid' => $rec->uuid,
    ])->assertSee('"deleted"')->assertSee($rec->uuid);

    expect(StudioRecord::withTrashed()->where('uuid', $rec->uuid)->first()->deleted_at)->not->toBeNull();
});

it('hard-deletes when force=true', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($col, 'collection')->create([
        'tenant_id' => 1, 'column_name' => 'name', 'field_type' => 'text', 'eav_cast' => 'text',
    ]);
    $rec = EavQueryBuilder::for($col)->create(['name' => 'Goner']);

    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Destroy->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, DeleteRecordTool::class, [
        'collection_slug' => 'products',
        'uuid' => $rec->uuid,
        'force' => true,
    ])->assertSee('"deleted"')->assertSee($rec->uuid);

    expect(StudioRecord::withTrashed()->where('uuid', $rec->uuid)->exists())->toBeFalse();
});
