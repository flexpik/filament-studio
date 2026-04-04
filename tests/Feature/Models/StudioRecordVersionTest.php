<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Illuminate\Foundation\Auth\User;

it('can be created with factory', function () {
    $version = StudioRecordVersion::factory()->create();

    expect($version)
        ->toBeInstanceOf(StudioRecordVersion::class)
        ->snapshot->toBeArray();
});

it('casts snapshot as array', function () {
    $data = ['name' => 'Widget', 'price' => 49.99];
    $version = StudioRecordVersion::factory()->create(['snapshot' => $data]);

    expect($version->snapshot)->toBe($data);
});

it('belongs to a record', function () {
    $version = StudioRecordVersion::factory()->create();

    expect($version->record)->toBeInstanceOf(StudioRecord::class);
});

it('belongs to a collection', function () {
    $version = StudioRecordVersion::factory()->create();

    expect($version->collection)->toBeInstanceOf(StudioCollection::class);
});

it('scopes by tenant', function () {
    StudioRecordVersion::factory()->create(['tenant_id' => 1]);
    StudioRecordVersion::factory()->create(['tenant_id' => 2]);

    expect(StudioRecordVersion::forTenant(1)->count())->toBe(1);
});

it('belongs to a creator user', function () {
    $user = User::forceCreate([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => bcrypt('password'),
    ]);

    $version = StudioRecordVersion::factory()->create([
        'created_by' => $user->id,
    ]);

    expect($version->creator)
        ->toBeInstanceOf(User::class)
        ->name->toBe('Jane Doe');
});

it('returns null creator when created_by is null', function () {
    $version = StudioRecordVersion::factory()->create([
        'created_by' => null,
    ]);

    expect($version->creator)->toBeNull();
});
