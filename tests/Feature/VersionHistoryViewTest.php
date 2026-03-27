<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'slug' => 'contacts',
        'enable_versioning' => true,
    ]);

    $this->field = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'label' => 'Full Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
});

it('eager-loads creator relationship on versions', function () {
    $user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    StudioRecordVersion::factory()->create([
        'record_id' => $this->record->id,
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'created_by' => $user->id,
        'snapshot' => ['name' => 'Alice'],
    ]);

    $versions = StudioRecordVersion::with('creator')
        ->where('record_id', $this->record->id)
        ->orderByDesc('created_at')
        ->get();

    expect($versions)->toHaveCount(1)
        ->and($versions->first()->relationLoaded('creator'))->toBeTrue()
        ->and($versions->first()->creator->name)->toBe('Test User');
});

it('builds field labels map from collection fields', function () {
    $fieldLabels = $this->collection->fields()
        ->pluck('label', 'column_name')
        ->all();

    expect($fieldLabels)->toBe(['name' => 'Full Name']);
});

it('computes diff between consecutive version snapshots', function () {
    StudioRecordVersion::factory()->create([
        'record_id' => $this->record->id,
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'snapshot' => ['name' => 'Alice'],
        'created_at' => now()->subHours(2),
    ]);

    StudioRecordVersion::factory()->create([
        'record_id' => $this->record->id,
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'snapshot' => ['name' => 'Bob'],
        'created_at' => now()->subHour(),
    ]);

    $versions = StudioRecordVersion::where('record_id', $this->record->id)
        ->orderByDesc('created_at')
        ->get();

    // Newest first: Bob (v2), Alice (v1)
    $latest = $versions->first();
    $oldest = $versions->last();

    // Diff: Bob vs Alice — name changed
    expect($latest->snapshot['name'])->toBe('Bob')
        ->and($oldest->snapshot['name'])->toBe('Alice')
        ->and($latest->snapshot['name'])->not->toBe($oldest->snapshot['name']);
});
