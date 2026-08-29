<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('adds attempt_number, error_class, branch_taken to studio_flow_run_steps', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_run_steps';

    expect(Schema::hasColumn($table, 'attempt_number'))->toBeTrue();
    expect(Schema::hasColumn($table, 'error_class'))->toBeTrue();
    expect(Schema::hasColumn($table, 'error_trace'))->toBeTrue();
    expect(Schema::hasColumn($table, 'branch_taken'))->toBeTrue();
});

it('adds dry_run boolean (default false) to studio_flow_runs', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_runs';

    expect(Schema::hasColumn($table, 'dry_run'))->toBeTrue();
});

it('has dashboard widget indexes on studio_flow_runs', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_runs';
    $indexes = collect(Schema::getIndexes($table))->pluck('columns');

    expect($indexes->contains(fn ($cols) => $cols == ['status', 'started_at']))->toBeTrue();
    expect($indexes->contains(fn ($cols) => $cols == ['flow_id', 'status', 'started_at']))->toBeTrue();
});
