<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simulates a pre-Phase-10 install (which ran the old create stubs) and asserts
 * the z_add_observability_columns_to_flow_runs_and_steps upgrade migration
 * reconciles the schema. Fresh installs already have these columns from the base
 * stubs, so the migration must also be a safe no-op when re-run.
 */
function loadObservabilityUpgradeMigration(): object
{
    return require dirname(__DIR__, 4).'/database/migrations/z_add_observability_columns_to_flow_runs_and_steps.php.stub';
}

function downgradeToPreP10Schema(): void
{
    $prefix = config('filament-studio.table_prefix', 'studio_');
    $runs = $prefix.'flow_runs';
    $steps = $prefix.'flow_run_steps';

    Schema::table($runs, function (Blueprint $table) {
        $table->dropIndex(['status', 'started_at']);
        $table->dropIndex(['flow_id', 'status', 'started_at']);
        $table->dropColumn('dry_run');
    });

    Schema::table($steps, function (Blueprint $table) {
        $table->dropIndex(['flow_run_id', 'operation_key']);
        $table->dropColumn(['error_class', 'branch_taken']);
        $table->renameColumn('attempt_number', 'attempt');
    });
}

it('reconciles a pre-P10 schema by adding the observability columns and indexes', function () {
    $prefix = config('filament-studio.table_prefix', 'studio_');
    $runs = $prefix.'flow_runs';
    $steps = $prefix.'flow_run_steps';

    downgradeToPreP10Schema();

    // Sanity: we are genuinely on the old shape.
    expect(Schema::hasColumn($runs, 'dry_run'))->toBeFalse();
    expect(Schema::hasColumn($steps, 'attempt'))->toBeTrue();
    expect(Schema::hasColumn($steps, 'attempt_number'))->toBeFalse();

    loadObservabilityUpgradeMigration()->up();

    expect(Schema::hasColumn($runs, 'dry_run'))->toBeTrue();
    expect(Schema::hasColumn($steps, 'attempt_number'))->toBeTrue();
    expect(Schema::hasColumn($steps, 'attempt'))->toBeFalse();
    expect(Schema::hasColumn($steps, 'error_class'))->toBeTrue();
    expect(Schema::hasColumn($steps, 'branch_taken'))->toBeTrue();
    expect(Schema::hasIndex($runs, ['status', 'started_at']))->toBeTrue();
    expect(Schema::hasIndex($runs, ['flow_id', 'status', 'started_at']))->toBeTrue();
    expect(Schema::hasIndex($steps, ['flow_run_id', 'operation_key']))->toBeTrue();
});

it('is a safe no-op when the P10 columns already exist (fresh install / re-run)', function () {
    $runs = config('filament-studio.table_prefix', 'studio_').'flow_runs';

    // Schema is already at P10 from the base stubs; running up() must not throw.
    loadObservabilityUpgradeMigration()->up();

    expect(Schema::hasColumn($runs, 'dry_run'))->toBeTrue();
});
