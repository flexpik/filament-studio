<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Tools\Records\GetRecordTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

it('returns a single record by uuid', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($col, 'collection')->create([
        'tenant_id' => 1, 'column_name' => 'name', 'field_type' => 'text', 'eav_cast' => 'text',
    ]);
    $rec = EavQueryBuilder::for($col)->create(['name' => 'Widget']);

    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Show->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, GetRecordTool::class, [
        'collection_slug' => 'products',
        'uuid' => $rec->uuid,
    ])->assertSee('Widget')->assertSee($rec->uuid);
});

it('returns STUDIO_NOT_FOUND when uuid is unknown', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Show->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, GetRecordTool::class, [
        'collection_slug' => 'products',
        'uuid' => '00000000-0000-0000-0000-000000000000',
    ])->assertSee('STUDIO_NOT_FOUND');
});
