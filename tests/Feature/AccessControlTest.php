<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Policies\StudioCollectionPolicy;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->policy = new StudioCollectionPolicy;
    $this->collection = StudioCollection::factory()->create(['tenant_id' => 1]);
});

it('allows viewAny by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows create by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->create($user))->toBeTrue();
});

it('allows update by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->update($user, $this->collection))->toBeTrue();
});

it('allows delete by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->delete($user, $this->collection))->toBeTrue();
});

it('allows manageFields by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->manageFields($user, $this->collection))->toBeTrue();
});

it('allows viewRecords by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->viewRecords($user, $this->collection))->toBeTrue();
});

it('allows createRecord by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->createRecord($user, $this->collection))->toBeTrue();
});

it('allows updateRecord by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->updateRecord($user, $this->collection))->toBeTrue();
});

it('allows deleteRecord by default', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);

    expect($this->policy->deleteRecord($user, $this->collection))->toBeTrue();
});

it('respects is_hidden for viewRecords when user lacks admin role', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);
    $hiddenCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'is_hidden' => true,
    ]);

    expect($this->policy->viewRecords($user, $hiddenCollection))->toBeTrue();
});
