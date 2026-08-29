<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the studio_flow_secrets table', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_secrets';
    expect(Schema::hasTable($table))->toBeTrue();

    foreach (['id', 'flow_id', 'key', 'value', 'created_at', 'updated_at'] as $column) {
        expect(Schema::hasColumn($table, $column))->toBeTrue("missing column {$column}");
    }
});

it('enforces unique key per flow on studio_flow_secrets', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_secrets';
    $indexes = collect(Schema::getIndexes($table))->pluck('columns');
    expect($indexes->contains(fn ($cols) => $cols == ['flow_id', 'key']))->toBeTrue();
});
