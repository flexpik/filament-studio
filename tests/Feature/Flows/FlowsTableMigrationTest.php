<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the studio_flows table with required columns', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flows';
    expect(Schema::hasTable($table))->toBeTrue();

    foreach ([
        'id', 'tenant_id', 'name', 'slug', 'description', 'icon', 'color',
        'status', 'logging_mode', 'webhook_secret', 'created_at', 'updated_at',
    ] as $column) {
        expect(Schema::hasColumn($table, $column))->toBeTrue("missing column {$column}");
    }
});

it('enforces unique slug per tenant on studio_flows', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flows';
    $indexes = collect(Schema::getIndexes($table))->pluck('columns');
    expect($indexes->contains(fn ($cols) => $cols == ['tenant_id', 'slug']))->toBeTrue();
});
