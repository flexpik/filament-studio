<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages\EditCollection;
use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);
    actingAs($this->user);
    config()->set('filament-studio.api.enabled', true);
});

it('displays api_enabled toggle on the edit form', function () {
    $collection = StudioCollection::factory()->create();

    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->assertFormFieldExists('api_enabled');
});

it('can toggle api_enabled on a collection', function () {
    $collection = StudioCollection::factory()->create(['api_enabled' => false]);

    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->fillForm(['api_enabled' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($collection->fresh()->api_enabled)->toBeTrue();
});
