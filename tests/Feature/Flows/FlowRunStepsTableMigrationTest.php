<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the studio_flow_run_steps table', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_run_steps';
    expect(Schema::hasTable($table))->toBeTrue();

    foreach ([
        'id', 'flow_run_id', 'operation_key', 'operation_type', 'attempt_number',
        'status', 'input', 'output', 'error_message', 'error_trace',
        'error_class', 'branch_taken', 'started_at', 'finished_at',
    ] as $column) {
        expect(Schema::hasColumn($table, $column))->toBeTrue("missing column {$column}");
    }
});
