<?php

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    FilamentStudioPlugin::resetHooks();
});

it('fires afterCollectionCreated hook', function () {
    $firedWith = null;

    FilamentStudioPlugin::afterCollectionCreated(function (StudioCollection $collection) use (&$firedWith) {
        $firedWith = $collection;
    });

    $collection = StudioCollection::factory()->create();
    FilamentStudioPlugin::fireAfterCollectionCreated($collection);

    expect($firedWith)->toBeInstanceOf(StudioCollection::class)
        ->and($firedWith->id)->toBe($collection->id);
});

it('fires afterFieldAdded hook', function () {
    $firedWith = null;

    FilamentStudioPlugin::afterFieldAdded(function (StudioField $field) use (&$firedWith) {
        $firedWith = $field;
    });

    $field = StudioField::factory()->create();
    FilamentStudioPlugin::fireAfterFieldAdded($field);

    expect($firedWith)->toBeInstanceOf(StudioField::class)
        ->and($firedWith->id)->toBe($field->id);
});

it('modifies form schema via hook', function () {
    FilamentStudioPlugin::modifyFormSchema(function (array $schema, StudioCollection $collection) {
        $schema[] = Placeholder::make('injected')
            ->content('Hook injected');

        return $schema;
    });

    $collection = StudioCollection::factory()->create();
    $schema = [TextInput::make('name')];

    $modified = FilamentStudioPlugin::applyModifyFormSchema($schema, $collection);

    expect($modified)->toHaveCount(2);
});

it('modifies table columns via hook', function () {
    FilamentStudioPlugin::modifyTableColumns(function (array $columns, StudioCollection $collection) {
        $columns[] = TextColumn::make('extra');

        return $columns;
    });

    $collection = StudioCollection::factory()->create();
    $columns = [TextColumn::make('name')];

    $modified = FilamentStudioPlugin::applyModifyTableColumns($columns, $collection);

    expect($modified)->toHaveCount(2);
});

it('modifies query via hook', function () {
    $hookCalled = false;

    FilamentStudioPlugin::modifyQuery(function ($query) use (&$hookCalled) {
        $hookCalled = true;

        return $query;
    });

    $collection = StudioCollection::factory()->create();
    $query = new EavQueryBuilder($collection);

    FilamentStudioPlugin::applyModifyQuery($query);

    expect($hookCalled)->toBeTrue();
});

it('supports multiple callbacks per hook', function () {
    $callCount = 0;

    FilamentStudioPlugin::afterCollectionCreated(function () use (&$callCount) {
        $callCount++;
    });
    FilamentStudioPlugin::afterCollectionCreated(function () use (&$callCount) {
        $callCount++;
    });

    $collection = StudioCollection::factory()->create();
    FilamentStudioPlugin::fireAfterCollectionCreated($collection);

    expect($callCount)->toBe(2);
});

it('resets all hooks', function () {
    FilamentStudioPlugin::afterCollectionCreated(fn () => null);
    FilamentStudioPlugin::afterFieldAdded(fn () => null);
    FilamentStudioPlugin::modifyFormSchema(fn ($s, $c) => $s);
    FilamentStudioPlugin::modifyTableColumns(fn ($c, $col) => $c);
    FilamentStudioPlugin::modifyQuery(fn ($q) => $q);
    FilamentStudioPlugin::afterTenantCreatedHook(fn () => null);

    FilamentStudioPlugin::resetHooks();

    $collection = StudioCollection::factory()->create();
    FilamentStudioPlugin::fireAfterCollectionCreated($collection);

    expect(true)->toBeTrue();
});
