<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the studio_flow_runs table', function () {
    $table = config('filament-studio.table_prefix', 'studio_').'flow_runs';
    expect(Schema::hasTable($table))->toBeTrue();

    foreach ([
        'id', 'flow_id', 'flow_version_id', 'status', 'trigger_type',
        'trigger_payload', 'accountability', 'started_at', 'finished_at',
        'duration_ms', 'dry_run',
    ] as $column) {
        expect(Schema::hasColumn($table, $column))->toBeTrue("missing column {$column}");
    }
});
