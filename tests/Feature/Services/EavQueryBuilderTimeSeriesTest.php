<?php

use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\GroupPrecision;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

mutates(EavQueryBuilder::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    $this->dateField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'created_at',
        'field_type' => 'datetime',
        'eav_cast' => EavCast::Datetime,
    ]);

    $this->amountField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'amount',
        'field_type' => 'decimal',
        'eav_cast' => EavCast::Decimal,
    ]);

    $dates = [
        '2026-01-15 10:00:00' => 100.00,
        '2026-01-20 12:00:00' => 200.00,
        '2026-02-10 09:00:00' => 150.00,
        '2026-03-05 14:00:00' => 300.00,
    ];

    foreach ($dates as $date => $amount) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->dateField->id,
            'val_datetime' => $date,
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->amountField->id,
            'val_decimal' => $amount,
        ]);
    }
});

it('groups count by month', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateTimeSeries(
            AggregateFunction::Count,
            'amount',
            'created_at',
            GroupPrecision::Month,
        );

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(3)
        ->and($result->get('2026-01'))->toBe(2)
        ->and($result->get('2026-02'))->toBe(1)
        ->and($result->get('2026-03'))->toBe(1);
});

it('groups sum by month', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateTimeSeries(
            AggregateFunction::Sum,
            'amount',
            'created_at',
            GroupPrecision::Month,
        );

    expect((float) $result->get('2026-01'))->toBe(300.00)
        ->and((float) $result->get('2026-02'))->toBe(150.00)
        ->and((float) $result->get('2026-03'))->toBe(300.00);
});

it('returns empty collection when no data matches', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(99)
        ->aggregateTimeSeries(
            AggregateFunction::Count,
            'amount',
            'created_at',
            GroupPrecision::Month,
        );

    expect($result)->toBeEmpty();
});
