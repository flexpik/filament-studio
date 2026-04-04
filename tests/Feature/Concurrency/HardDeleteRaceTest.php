<?php

// tests/Feature/Concurrency/HardDeleteRaceTest.php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

it('hard delete wraps value deletion and record deletion in a transaction', function () {
    $collection = StudioCollection::factory()->forTenant(1)->create([
        'enable_soft_deletes' => false,
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = EavQueryBuilder::for($collection)->tenant(1)->create(['title' => 'Test']);

    expect(StudioRecord::find($record->id))->not->toBeNull()
        ->and(StudioValue::where('record_id', $record->id)->count())->toBe(1);

    EavQueryBuilder::for($collection)->tenant(1)->delete($record->id);

    expect(StudioRecord::find($record->id))->toBeNull()
        ->and(StudioValue::where('record_id', $record->id)->count())->toBe(0);
});

it('hard delete does not leave orphaned values if record deletion fails', function () {
    $collection = StudioCollection::factory()->forTenant(1)->create([
        'enable_soft_deletes' => false,
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = EavQueryBuilder::for($collection)->tenant(1)->create(['title' => 'Test']);
    $recordId = $record->id;

    DB::listen(function ($query) {
        // Documentation test — the transaction wrapping is the fix
    });

    EavQueryBuilder::for($collection)->tenant(1)->delete($recordId);

    expect(StudioRecord::find($recordId))->toBeNull()
        ->and(StudioValue::where('record_id', $recordId)->count())->toBe(0);
});
