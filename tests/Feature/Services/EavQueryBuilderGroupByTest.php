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

    $this->statusField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'status',
        'field_type' => 'select',
        'eav_cast' => EavCast::Text,
    ]);

    $this->amountField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'amount',
        'field_type' => 'decimal',
        'eav_cast' => EavCast::Decimal,
    ]);

    $data = [
        ['status' => 'active', 'amount' => 100.00],
        ['status' => 'active', 'amount' => 200.00],
        ['status' => 'pending', 'amount' => 50.00],
    ];

    foreach ($data as $row) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::create(['record_id' => $record->id, 'field_id' => $this->statusField->id, 'val_text' => $row['status']]);
        StudioValue::create(['record_id' => $record->id, 'field_id' => $this->amountField->id, 'val_decimal' => $row['amount']]);
    }
});

it('groups count by field value', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateByGroup(
            AggregateFunction::Count,
            'amount',
            'status',
        );

    expect($result)->toHaveCount(2)
        ->and($result->get('active'))->toBe(2)
        ->and($result->get('pending'))->toBe(1);
});

it('groups sum by field value', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateByGroup(
            AggregateFunction::Sum,
            'amount',
            'status',
        );

    expect((float) $result->get('active'))->toBe(300.00)
        ->and((float) $result->get('pending'))->toBe(50.00);
});

it('returns empty collection when no records match', function () {
    $result = EavQueryBuilder::for($this->collection)
        ->tenant(99)
        ->aggregateByGroup(AggregateFunction::Count, 'amount', 'status');

    expect($result)->toBeEmpty();
});
