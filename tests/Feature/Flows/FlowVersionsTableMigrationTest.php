<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the studio_flow_versions table', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_versions';
    expect(Schema::hasTable($table))->toBeTrue();

    foreach ([
        'id', 'flow_id', 'version', 'graph', 'published_at',
        'change_summary', 'created_at',
    ] as $column) {
        expect(Schema::hasColumn($table, $column))->toBeTrue("missing column {$column}");
    }
});

it('enforces unique version per flow', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_versions';
    $indexes = collect(Schema::getIndexes($table))->pluck('columns');
    expect($indexes->contains(fn ($cols) => $cols == ['flow_id', 'version']))->toBeTrue();
});
