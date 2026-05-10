<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource;

it('saves _studio scopes to permissions when checked in the form', function () {
    $key = StudioApiKey::factory()->create();

    $form = ApiSettingsResource::form(Schema::make());

    // Simulate a form submission applying the MCP scopes section
    $key->permissions = array_merge(
        $key->permissions ?? [],
        ['_studio' => ['manage_collections', 'read_schema']],
    );
    $key->save();

    expect($key->fresh()->canManage(StudioScope::ManageCollections))->toBeTrue();
    expect($key->fresh()->canManage(StudioScope::ReadSchema))->toBeTrue();
    expect($key->fresh()->canManage(StudioScope::ManageDashboards))->toBeFalse();
});

it('exposes all StudioScope cases in the form options helper', function () {
    $options = StudioScope::asSelectOptions();

    expect($options)->toHaveCount(5);
    expect(array_keys($options))->toBe([
        '_studio.manage_collections',
        '_studio.manage_dashboards',
        '_studio.manage_filters',
        '_studio.manage_api_keys',
        '_studio.read_schema',
    ]);
});
