<?php

use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

mutates(EavQueryBuilder::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => EavCast::Decimal,
    ]);

    foreach ([10.00, 20.00, 30.00] as $price) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->priceField->id,
            'val_decimal' => $price,
        ]);
    }
});

it('computes count aggregate', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Count, 'price');

    expect($result)->toBe(3);
});

it('computes sum aggregate', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Sum, 'price');

    expect((float) $result)->toBe(60.00);
});

it('computes avg aggregate', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Avg, 'price');

    expect((float) $result)->toBe(20.00);
});

it('computes min aggregate', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Min, 'price');

    expect((float) $result)->toBe(10.00);
});

it('computes max aggregate', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Max, 'price');

    expect((float) $result)->toBe(30.00);
});

it('applies filters to aggregate', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('price', '>', 15)
        ->aggregate(AggregateFunction::Count, 'price');

    expect($result)->toBe(2);
});

it('returns null for aggregate on empty result set', function () {
    $emptyCollection = StudioCollection::factory()->create(['tenant_id' => 99]);
    StudioField::factory()->create([
        'collection_id' => $emptyCollection->id,
        'tenant_id' => 99,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => EavCast::Decimal,
    ]);

    $result = EavQueryBuilder::for($emptyCollection)
        ->tenant(99)
        ->aggregate(AggregateFunction::Sum, 'price');

    expect($result)->toBeNull();
});
