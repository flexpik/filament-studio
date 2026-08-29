<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ViewFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('shows error_class but hides stack trace for non-admin', function () {
    $user = $this->makeUserWith(['view_flows']);
    $this->actingAs($user);

    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'status' => FlowRunStatus::Failed,
    ]);

    StudioFlowRunStep::factory()->for($run, 'run')->create([
        'operation_key' => 'op_fail',
        'status' => FlowRunStepStatus::Failed,
        'error_class' => 'RuntimeException',
        'error_message' => 'Something went wrong',
        'error_trace' => '#0 /path/to/file.php(42): someMethod()',
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee('RuntimeException')
        ->assertSee('Something went wrong')
        ->assertDontSee('#0 /path/to/file.php(42): someMethod()');
});

it('shows full stack trace for admin', function () {
    Role::firstOrCreate(['name' => 'studio-admin', 'guard_name' => 'web']);
    $admin = $this->makeUserWith(['view_flows']);
    $admin->assignRole('studio-admin');
    $this->actingAs($admin);

    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'status' => FlowRunStatus::Failed,
    ]);

    StudioFlowRunStep::factory()->for($run, 'run')->create([
        'operation_key' => 'op_fail',
        'status' => FlowRunStepStatus::Failed,
        'error_class' => 'RuntimeException',
        'error_message' => 'Something went wrong',
        'error_trace' => '#0 /path/to/file.php(42): someMethod()',
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee('RuntimeException')
        ->assertSee('Something went wrong')
        ->assertSee('#0 /path/to/file.php(42): someMethod()');
});
