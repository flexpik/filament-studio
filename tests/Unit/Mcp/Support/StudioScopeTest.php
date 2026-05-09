<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\StudioScope;

it('exposes all five management scopes prefixed with _studio.', function () {
    $values = array_map(fn (StudioScope $s) => $s->value, StudioScope::cases());

    expect($values)->toBe([
        '_studio.manage_collections',
        '_studio.manage_dashboards',
        '_studio.manage_filters',
        '_studio.manage_api_keys',
        '_studio.read_schema',
    ]);
});

it('returns the bare scope name (after the dot) via name()', function () {
    expect(StudioScope::ManageCollections->name())->toBe('manage_collections');
    expect(StudioScope::ReadSchema->name())->toBe('read_schema');
});

it('returns a human label for each scope', function () {
    expect(StudioScope::ManageCollections->label())->toBe('Manage Collections');
    expect(StudioScope::ManageDashboards->label())->toBe('Manage Dashboards');
    expect(StudioScope::ManageFilters->label())->toBe('Manage Saved Filters');
    expect(StudioScope::ManageApiKeys->label())->toBe('Manage API Keys');
    expect(StudioScope::ReadSchema->label())->toBe('Read Schema (Read-Only)');
});
