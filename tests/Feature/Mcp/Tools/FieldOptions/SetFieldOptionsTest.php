<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\FieldOptions\SetFieldOptionsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;

it('replaces field options via tool', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'status', 'field_type' => 'select', 'tenant_id' => 1,
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, SetFieldOptionsTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'status',
        'options' => [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'published', 'label' => 'Published'],
        ],
    ])->assertSee('draft');

    expect(StudioFieldOption::count())->toBe(2);
});

it('returns STUDIO_VALIDATION_FAILED for non-options field type', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'sku', 'field_type' => 'text', 'tenant_id' => 1,
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, SetFieldOptionsTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'sku',
        'options' => [['value' => 'x', 'label' => 'X']],
    ])->assertSee('STUDIO_VALIDATION_FAILED');
});
