<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Collections\UpdateCollection;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->action = new UpdateCollection);

it('updates collection meta in place', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1, 'name' => 'Old']);

    $updated = ($this->action)('products', ['name' => 'New', 'icon' => 'heroicon-o-cube'], 1);

    expect($updated->name)->toBe('New')->and($updated->icon)->toBe('heroicon-o-cube');
});

it('throws StudioNotFoundException for unknown slug', function () {
    expect(fn () => ($this->action)('missing', ['name' => 'X'], 1))
        ->toThrow(StudioNotFoundException::class);
});

it('respects tenant scoping', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 2]);

    expect(fn () => ($this->action)('products', ['name' => 'X'], 1))
        ->toThrow(StudioNotFoundException::class);
});

it('rejects attempts to change slug', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    expect(fn () => ($this->action)('products', ['slug' => 'items'], 1))
        ->toThrow(ValidationException::class);
});
