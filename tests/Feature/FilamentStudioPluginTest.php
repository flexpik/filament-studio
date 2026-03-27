<?php

use Flexpik\FilamentStudio\FilamentStudioPlugin;

it('can be instantiated via make()', function () {
    $plugin = FilamentStudioPlugin::make();

    expect($plugin)->toBeInstanceOf(FilamentStudioPlugin::class);
});

it('has a plugin ID', function () {
    $plugin = FilamentStudioPlugin::make();

    expect($plugin->getId())->toBe('filament-studio');
});

it('supports fluent configuration', function () {
    $plugin = FilamentStudioPlugin::make()
        ->navigationGroup('Data')
        ->schemaNavigationLabel('Schema Manager');

    expect($plugin->getNavigationGroup())->toBe('Data')
        ->and($plugin->getSchemaNavigationLabel())->toBe('Schema Manager');
});

it('supports versioning and soft deletes configuration', function () {
    $plugin = FilamentStudioPlugin::make()
        ->enableVersioning()
        ->enableSoftDeletes();

    expect($plugin->isVersioningEnabled())->toBeTrue()
        ->and($plugin->isSoftDeletesEnabled())->toBeTrue();
});

it('supports afterTenantCreated callback', function () {
    $called = false;
    $plugin = FilamentStudioPlugin::make()
        ->afterTenantCreated(function () use (&$called) {
            $called = true;
        });

    expect($plugin->getAfterTenantCreatedCallback())->toBeCallable();
});
