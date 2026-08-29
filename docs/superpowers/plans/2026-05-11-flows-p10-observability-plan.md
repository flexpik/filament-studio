# Phase 10 — Observability & Debugging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship four observability surfaces: richer run-detail page, four dashboard widgets, canvas dry-run + step-by-step modes, structured per-run + bulk run-export endpoints.

**Architecture:** Extend studio_flow_run_steps with retry/error/branch columns. Add dry_run flag on runs. Filament widgets read indexed run queries. Dry-run is a side-effect-free execution mode tagged on the run. Step-through uses a cache-backed resume-token pattern, canvas-only. Export endpoints stream JSONL/CSV with cursor pagination.

**Tech Stack:** Laravel 12, Filament v5 widgets, Pest v4 (including browser), durable-workflow, React/xyflow canvas, Zustand.

**Branch:** `feat/flows-p10-observability` (cut from `release/flows-2.0` after Phase 8 merges)

**Depends on:** Phase 7 (version pinning + replay), Phase 8 (sensitive masking + audit log).

---

## Notes for the implementer

- Package root: `/var/www/html/crud/packages/flexpik/filament-studio/`. All commits land in the package repo with author `Serhii Fedorenko <drserhii@gmail.com>`. **No AI attribution / Co-Authored-By lines.**
- Migrations are `.php.stub` files in `database/migrations/` — copy/modify the relevant stubs (already include `attempt`, `error_trace`; this phase adds the rest).
- Existing classes already in tree:
  - `src/Flows/Engine/FlowWorkflow.php`, `src/Flows/Engine/FlowDispatcher.php`, `src/Flows/Engine/GraphWalker.php`, `src/Flows/Engine/FlowContext.php`, `src/Flows/Engine/LogMaskingService.php`
  - Filament pages: `src/Flows/Filament/Resources/FlowResource/Pages/{ListFlows,ListFlowRuns,ViewFlowRun,DesignFlow,EditFlow,CreateFlow}.php`
  - API controllers: `src/Api/Flows/Controllers/{FlowController,FlowRunController,FlowGraphController,FlowWebhookController,FlowMetaController}.php`
  - Canvas: `resources/js/flows/testrun/TestRunPanel.tsx` (Phase 6 commit).
- Tests:
  - PHP-only Pest tests live under `tests/Feature/Flows/` and `tests/Integration/Flows/` (use `SpatieTestCase` when permission gating matters).
  - Browser tests use Pest 4 (`visit`, `click`, `fill`, `assertSee`, `assertNoJavaScriptErrors`).
  - Canvas unit tests live under `resources/js/flows/__tests__/` (Vitest, already configured).
- Each task rhythm: write failing test → run and confirm fail → implement → run and confirm pass → `docker exec php83 /var/www/html/crud/vendor/bin/pint --dirty --format agent` → commit.
- **Do not commit at the end of the plan.** Wait for the human to review and merge.
- Run package tests via: `docker exec -w /var/www/html/crud/packages/flexpik/filament-studio -e XDEBUG_MODE=off php83 vendor/bin/pest --compact`
- Vitest: from package root, `npm run test --workspace=… ` or whatever the host-app convention is — confirm with existing canvas tests under `resources/js/flows/__tests__/`.

---

## Surface 1 — Richer run-detail page

### Task 1: Schema additions — run-step retry/error/branch columns + run dry_run flag + dashboard indexes

**Files:**
- Modify: `database/migrations/create_studio_flow_run_steps_table.php.stub`
- Modify: `database/migrations/create_studio_flow_runs_table.php.stub`
- Modify: `src/Flows/Models/StudioFlowRunStep.php` — add casts/fillable
- Modify: `src/Flows/Models/StudioFlowRun.php` — add `dry_run` cast/fillable
- Test: `tests/Feature/Flows/Schema/Phase10SchemaTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('adds attempt_number, error_class, error_trace, branch_taken to studio_flow_run_steps', function () {
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
```

- [ ] **Step 2: Run failing test**

Expected: columns / indexes missing.

- [ ] **Step 3: Update stubs**

Add to `create_studio_flow_run_steps_table.php.stub`:

```php
$table->unsignedInteger('attempt_number')->default(1);
$table->string('error_class')->nullable();
// error_trace already present — verify and reuse.
$table->string('branch_taken')->nullable();
```

Add to `create_studio_flow_runs_table.php.stub`:

```php
$table->boolean('dry_run')->default(false);

$table->index(['status', 'started_at']);
$table->index(['flow_id', 'status', 'started_at']);
$table->index(['tenant_id', 'status', 'started_at']); // only if tenant_id column exists; otherwise skip.
```

- [ ] **Step 4: Update models**

Add `dry_run` to `StudioFlowRun::$casts` (`'dry_run' => 'boolean'`) and `attempt_number`, `error_class`, `error_trace`, `branch_taken` to `StudioFlowRunStep` casts/fillable.

- [ ] **Step 5: Run passing test, pint, commit**

Commit: `feat(flows): add p10 schema — retry/error/branch on steps, dry_run + indexes on runs`

---

### Task 2: Engine — populate attempt_number, error_class/trace, branch_taken

**Files:**
- Modify: `src/Flows/Engine/FlowWorkflow.php` (or whichever class wraps per-operation execution + catches exceptions)
- Modify: `src/Flows/Engine/GraphWalker.php` — record `branch_taken` on condition/switch ops
- Test: `tests/Feature/Flows/Engine/StepInstrumentationTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
// ...factory + flow setup helpers from existing tests...

it('records attempt_number on retried steps', function () {
    // Build a flow with an HTTP op configured to fail twice and succeed on attempt 3.
    // Dispatch synchronously.
    $steps = StudioFlowRunStep::query()
        ->where('flow_run_id', $run->id)
        ->orderBy('attempt_number')
        ->get();

    expect($steps->pluck('attempt_number')->all())->toEqual([1, 2, 3]);
});

it('captures error_class and error_trace on failure', function () {
    // Flow that throws RuntimeException on a single op, no retry.
    $step = StudioFlowRunStep::where('flow_run_id', $run->id)->first();

    expect($step->error_class)->toBe(RuntimeException::class);
    expect($step->error_trace)->toContain('RuntimeException');
});

it('sets branch_taken for condition operations', function () {
    // Condition op evaluates to true.
    $step = StudioFlowRunStep::where('operation_type', 'condition')->first();

    expect($step->branch_taken)->toBe('true');
});

it('sets branch_taken for switch operations to the matched case key', function () {
    // Switch op with case "premium" matched.
    $step = StudioFlowRunStep::where('operation_type', 'switch')->first();

    expect($step->branch_taken)->toBe('premium');
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

In `FlowWorkflow` (or the per-step executor): seed `attempt_number = 1` on first attempt, increment on each retry insert. Capture `$throwable::class` into `error_class` and `$throwable->getTraceAsString()` into `error_trace` on the catch path.

In `GraphWalker` (or condition/switch op handlers): after evaluating the branch, write the chosen branch label into the step's `branch_taken` column before completing the step.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): instrument run steps with attempt/error/branch metadata`

---

### Task 3: Run-detail UI — step timeline, JSON viewer, masked I/O, retry grouping, version pill

**Files:**
- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/ViewFlowRun.php`
- Create: `resources/views/flows/filament/run-detail.blade.php` (or inline Livewire blade) — timeline + duration bars + tree viewer
- Test: `tests/Integration/Flows/Filament/RunDetailStepTimelineTest.php`
- Test: `tests/Integration/Flows/Filament/RunDetailMaskedOutputTest.php`
- Test: `tests/Integration/Flows/Filament/RunDetailRetryHistoryTest.php`
- Test: `tests/Integration/Flows/Filament/RunDetailErrorStackPermissionTest.php`

- [ ] **Step 1: Write failing tests**

```php
// RunDetailStepTimelineTest
it('renders step timeline with status, duration, and ordering', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    // Seed run with 3 steps: success (100ms), failed (50ms), skipped (0ms).

    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertSee('100ms')
        ->assertSee('50ms')
        ->assertSeeInOrder(['Step A', 'Step B', 'Step C'])
        ->assertSeeHtml('data-status="success"')
        ->assertSeeHtml('data-status="failed"')
        ->assertSeeHtml('data-status="skipped"');
});

it('renders the "Ran on version vN" pill linking to version history', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertSee('Ran on version v3');
});

it('shows "Ran on draft (inline snapshot)" for test runs', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $testRun->id])
        ->assertSee('Ran on draft (inline snapshot)');
});

// RunDetailMaskedOutputTest
it('renders step outputs with sensitive values masked per Phase 8', function () {
    // Seed step with input/output containing a known sensitive key (e.g. password).
    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertSee('***')
        ->assertDontSee('hunter2');
});

// RunDetailRetryHistoryTest
it('groups attempts under their step when attempt_number > 1', function () {
    // Seed 3 attempts of the same operation_key.
    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertSee('Attempt 1')
        ->assertSee('Attempt 2')
        ->assertSee('Attempt 3');
});

// RunDetailErrorStackPermissionTest
it('shows error_class but hides stack trace for non-admin', function () {
    $this->actingAs($this->makeUserWith(['view_flows'])); // no admin role
    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertSee('RuntimeException')
        ->assertDontSee($step->error_trace);
});

it('shows full stack trace for admin', function () {
    $this->actingAs($this->makeUserWith(['view_flows'], adminRole: true));
    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertSee('RuntimeException')
        ->assertSee('at App\\Http\\...');
});
```

- [ ] **Step 2: Run failing tests**

- [ ] **Step 3: Implement the page**

Add a Livewire-rendered timeline section to `ViewFlowRun` with:

- Step rows sorted by `started_at`, grouped by `operation_key` when multiple attempts exist.
- Duration bar widths proportional to `finished_at - started_at`, color-coded via `data-status`.
- Collapsible JSON tree (Alpine/Filament JS) for input/output. Pass already-stored (masked-at-write) values through; if not yet masked at write, route through `LogMaskingService::mask()`.
- Error panel: always show `error_class` + `error_message`; conditionally render `error_trace` if `auth()->user()->hasRole('studio-admin')`.
- Trigger payload section: collapsible card showing `studio_flow_runs.trigger_payload` (already masked at write per Phase 8).
- Version pill: read `flow_version_id` and render label + link to Phase 7's version-history viewer. If the run was a canvas test run (use the Phase 7 inline-snapshot marker, e.g. null `flow_version_id` or a dedicated boolean) show "Ran on draft (inline snapshot)".

- [ ] **Step 4: Run passing tests, pint, commit**

Commit: `feat(flows): richer run-detail page — timeline, retries, masked I/O, version pill`

---

### Task 4: Replay button — endpoint + UI

**Files:**
- Modify: `src/Api/Flows/Controllers/FlowRunController.php` — add `replay($id)`
- Modify: `src/Api/Flows/StudioFlowsApiRouteRegistrar.php` — add `POST /runs/{id}/replay`
- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/ViewFlowRun.php` — add header action
- Test: `tests/Feature/Flows/Api/RunDetailReplayTest.php`
- Test: `tests/Feature/Flows/Api/ReplayBlockedWhenNoPublishedVersionTest.php`

- [ ] **Step 1: Write failing tests**

```php
// RunDetailReplayTest
it('replays a run against the current published version with original payload', function () {
    // Seed: flow v1 published, v2 published (current). Original run on v1 with payload X.
    $response = $this->withHeaders(['X-Api-Key' => $key->key])
        ->postJson("/api/studio/flows/runs/{$run->id}/replay");

    $response->assertCreated();
    $newRunId = $response->json('data.id');
    $new = StudioFlowRun::find($newRunId);

    expect($new->flow_version_id)->toBe($v2->id);
    expect($new->trigger_payload)->toEqual($run->trigger_payload);
    expect($new->id)->not->toBe($run->id);
});

it('returns 422 with explicit reason when there is no current published version', function () {
    // Flow has no published version (only a draft).
    $this->withHeaders(['X-Api-Key' => $key->key])
        ->postJson("/api/studio/flows/runs/{$run->id}/replay")
        ->assertStatus(422)
        ->assertJsonPath('errors.replay.0', 'No current published version');
});

it('requires view_flows + execute_flows permission', function () {
    $this->withHeaders(['X-Api-Key' => $unprivilegedKey->key])
        ->postJson("/api/studio/flows/runs/{$run->id}/replay")
        ->assertForbidden();
});

// UI smoke
it('renders a Replay action on the run-detail page that warns about version mismatch', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $run->id])
        ->assertActionExists('replay')
        ->callAction('replay')
        ->assertSee('current published version (v2), not the version this run used (v1)');
});

it('disables the Replay action when no current published version exists', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $unpublishedRun->id])
        ->assertActionDisabled('replay');
});
```

- [ ] **Step 2: Run failing tests**

- [ ] **Step 3: Implement**

- Controller method resolves the original run, asserts the same tenant, looks up the flow's current published version (Phase 7 helper), and dispatches a new run with the original `trigger_payload`. If no published version exists, return `422` with a `replay` error.
- Route registration: `Route::post('runs/{run}/replay', [FlowRunController::class, 'replay'])`.
- Filament header action with confirmation modal text that interpolates the run's version and the current published version. Disabled when current published version is null.

- [ ] **Step 4: Run passing tests, pint, commit**

Commit: `feat(flows): replay endpoint and UI action with version-mismatch confirmation`

---

## Surface 2 — Dashboard widgets

> All widget queries **exclude `dry_run = true` by default** and respect tenant scope. Each task: seed dataset → assert numbers → implement.

### Task 5: `RecentFlowRunsWidget`

**Files:**
- Create: `src/Flows/Filament/Widgets/RecentFlowRunsWidget.php`
- Modify: `src/FilamentStudioPlugin.php` — register widget (opt-in, admin adds to dashboard)
- Test: `tests/Integration/Flows/Filament/Widgets/RecentFlowRunsWidgetTest.php`

- [ ] **Step 1: Write failing test**

```php
it('returns the last 20 runs, ordered by started_at desc, excluding dry runs', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    StudioFlowRun::factory()->count(25)->create();
    StudioFlowRun::factory()->dryRun()->count(5)->create(); // factory state

    $rows = Livewire::test(RecentFlowRunsWidget::class)
        ->call('getTableRecords')
        ->get('records');

    expect($rows)->toHaveCount(20);
    expect($rows->every(fn ($r) => $r->dry_run === false))->toBeTrue();
});

it('scopes to the current tenant', function () {
    // Create runs in two tenants; widget only returns current.
});

it('shows a "View" action linking to the run-detail page', function () {
    Livewire::test(RecentFlowRunsWidget::class)
        ->assertTableActionExists('view');
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

Filament v5 table widget. Query: `StudioFlowRun::query()->where('dry_run', false)->latest('started_at')->limit(20)`. Columns: flow name, status badge, started_at, duration. Row action: `view` linking to `ViewFlowRun::getUrl(['record' => $record])`.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): RecentFlowRunsWidget`

---

### Task 6: `FlowFailureRateWidget`

**Files:**
- Create: `src/Flows/Filament/Widgets/FlowFailureRateWidget.php`
- Test: `tests/Integration/Flows/Filament/Widgets/FlowFailureRateWidgetTest.php`

- [ ] **Step 1: Write failing test**

```php
it('computes failure rate over the last 24h', function () {
    // Seed: 10 runs in last 24h, 3 failed.
    $stats = (new FlowFailureRateWidget())->getStats();

    expect($stats[0]->getValue())->toBe('30%');
});

it('produces a 7-day sparkline trend (7 daily buckets)', function () {
    $stats = (new FlowFailureRateWidget())->getStats();
    expect($stats[0]->getDescriptionIcon())->toBe('heroicon-m-chart-bar');
    expect($stats[0]->getChart())->toHaveCount(7);
});

it('excludes dry runs from both the rate and the sparkline', function () {
    // Seed 100 dry-run failures; assert rate is computed without them.
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

Filament `StatsOverviewWidget` returning a single `Stat`. Use a single grouped query keyed off `(status, started_at)` — `started_at >= now()->sub('24 hours')`. For sparkline, group by `date(started_at)` over the last 7 days. Cache per-tenant for 60s.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): FlowFailureRateWidget with 24h rate + 7-day sparkline`

---

### Task 7: `TopFailingFlowsWidget`

**Files:**
- Create: `src/Flows/Filament/Widgets/TopFailingFlowsWidget.php`
- Test: `tests/Integration/Flows/Filament/Widgets/TopFailingFlowsWidgetTest.php`

- [ ] **Step 1: Write failing test**

```php
it('ranks flows by failure count in the last 24h', function () {
    // Seed: flow A 5 failures, flow B 2 failures, flow C 0 failures, all in last 24h.
    $rows = Livewire::test(TopFailingFlowsWidget::class)->call('getTableRecords')->get('records');

    expect($rows[0]->slug)->toBe('flow-a');
    expect($rows[0]->failure_count)->toBe(5);
    expect($rows[1]->slug)->toBe('flow-b');
    expect($rows)->not->toContain(fn ($r) => $r->slug === 'flow-c');
});

it('excludes dry runs', function () { /* ... */ });
it('scopes to current tenant', function () { /* ... */ });
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

Table widget. Query joins `studio_flows` and aggregates `count() where status='failed' AND dry_run=false AND started_at >= now()->sub('24h')`, ordered by count desc, limit 10. Row clickable → `EditFlow::getUrl(['record' => $flow])`.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): TopFailingFlowsWidget`

---

### Task 8: `FlowDurationWidget`

**Files:**
- Create: `src/Flows/Filament/Widgets/FlowDurationWidget.php`
- Test: `tests/Integration/Flows/Filament/Widgets/FlowDurationWidgetTest.php`

- [ ] **Step 1: Write failing test**

```php
it('computes avg and p95 duration over the last 24h', function () {
    // Seed durations [100, 100, 100, 100, 100, 100, 100, 100, 100, 5000].
    // avg = 590ms, p95 = ~5000ms (last value at 95th percentile).
    $stats = (new FlowDurationWidget())->getStats();

    expect($stats[0]->getValue())->toBe('590ms'); // avg
    expect($stats[1]->getValue())->toBe('5,000ms'); // p95
});

it('excludes dry runs', function () { /* ... */ });
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

Stats widget with two `Stat` entries. Avg via `AVG(duration_ms)`. P95: SQLite test runtime cannot use window functions reliably — compute in PHP from the windowed result set (`->pluck('duration_ms')` then percentile in code). Cache 60s per tenant.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): FlowDurationWidget with avg + p95`

---

## Surface 3 — Dry-run + step-by-step

### Task 9: Dry-run executor

**Files:**
- Create: `src/Flows/Engine/DryRunExecutor.php` (decorates / wraps `FlowWorkflow`)
- Modify: `src/Flows/Engine/FlowDispatcher.php` — accept `dry_run: true` option, instantiate `DryRunExecutor`
- Modify: `src/Flows/Operations/*` — operations consult `FlowContext::isDryRun()` (add accessor to `FlowContext`)
- Test: `tests/Feature/Flows/Engine/DryRunModeTest.php`

- [ ] **Step 1: Write failing test**

```php
it('marks the run with dry_run=true', function () {
    $run = (new FlowDispatcher())->dispatch($flow, payload: [], options: ['dry_run' => true]);
    expect($run->fresh()->dry_run)->toBeTrue();
});

it('returns synthetic results from Create Record without writing', function () {
    $beforeCount = StudioRecord::count();
    $run = (new FlowDispatcher())->dispatch($flowWithCreateRecord, [], ['dry_run' => true]);

    expect(StudioRecord::count())->toBe($beforeCount);
    $step = $run->steps()->where('operation_type', 'create_record')->first();
    expect($step->output['id'])->toStartWith('dry-run-');
});

it('returns input + dry_run=true from Update Record', function () {
    $step = /* ... */;
    expect($step->output)->toEqual([...$inputData, 'dry_run' => true]);
});

it('returns deleted=true from Delete Record', function () {
    expect($step->output)->toEqual(['deleted' => true, 'id' => $configuredId]);
});

it('skips side-effect operations (HTTP / email / notification / dispatch-job / fire-event / artisan) and logs a "[dry-run] would have called <target>" entry', function () {
    Http::fake();
    $run = (new FlowDispatcher())->dispatch($flowWithHttpOp, [], ['dry_run' => true]);

    Http::assertNothingSent();
    $step = $run->steps()->where('operation_type', 'http')->first();
    expect($step->status)->toBe('skipped');
    expect($step->output['log'])->toContain('[dry-run] would have called');
});

it('runs logic/transform/condition/switch/log-message operations normally', function () {
    // Assert a Transform op actually mutated the bag.
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

`DryRunExecutor` wraps each operation invocation, dispatching to one of three strategies based on operation type:

- **Data ops** (`create_record`, `update_record`, `delete_record`, `upsert_record`): return synthetic results per spec section "Dry-run mode", status `success`.
- **Side-effect ops** (`http`, `email`, `notification`, `dispatch_job`, `fire_event`, `artisan`): write `output = ['log' => '[dry-run] would have called …']`, status `skipped`. The classification list lives in a constant `DryRunExecutor::SIDE_EFFECT_TYPES`.
- **Pure ops** (everything else): execute normally.

`FlowDispatcher::dispatch(... options: ['dry_run' => true])` sets `dry_run=true` on the run record and selects `DryRunExecutor` instead of the regular per-op invoker. `FlowContext` exposes `isDryRun(): bool` so operations can branch internally if needed.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): dry-run execution mode (synthetic data ops, skipped side-effect ops)`

---

### Task 10: Canvas Test Run dry-run toggle

**Files:**
- Modify: `resources/js/flows/testrun/TestRunPanel.tsx` — add "Dry run" checkbox; include `dry_run` in POST body
- Modify: `src/Api/Flows/Controllers/FlowRunController.php` — accept `dry_run` in test-run endpoint; forward to dispatcher
- Modify: `src/Api/Flows/Requests/*TestRunRequest.php` — validate boolean
- Test: `tests/Feature/Flows/Api/TestRunDryRunFlagTest.php`
- Test: `resources/js/flows/__tests__/TestRunPanel.dryRun.test.tsx`

- [ ] **Step 1: Write failing tests**

```php
// TestRunDryRunFlagTest
it('passes dry_run=true through the test-run endpoint to the dispatcher', function () {
    $this->withHeaders(['X-Api-Key' => $key->key])
        ->postJson("/api/studio/flows/{$flow->id}/test-run", [
            'payload' => ['foo' => 'bar'],
            'dry_run' => true,
        ])
        ->assertCreated();

    expect(StudioFlowRun::latest('id')->first()->dry_run)->toBeTrue();
});
```

```tsx
// TestRunPanel.dryRun.test.tsx
it('posts dry_run=true when the toggle is checked', async () => {
    const fetchSpy = vi.spyOn(window, 'fetch').mockResolvedValue(/* … */);
    render(<TestRunPanel flowId="abc" />);
    fireEvent.click(screen.getByLabelText('Dry run'));
    fireEvent.click(screen.getByText('Run'));

    expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/test-run'),
        expect.objectContaining({ body: expect.stringContaining('"dry_run":true') }));
});
```

- [ ] **Step 2: Run failing tests**

- [ ] **Step 3: Implement**

Add a `dry_run` boolean to the test-run form-request validation rules. Forward into `FlowDispatcher::dispatch(..., ['dry_run' => $request->boolean('dry_run')])`. In `TestRunPanel.tsx`, render a checkbox bound to local state and include the boolean in the POST body.

- [ ] **Step 4: Run passing tests, pint, commit**

Commit: `feat(flows): canvas dry-run toggle (TestRunPanel + API)`

---

### Task 11: `StepThroughExecutor` (canvas-only)

**Files:**
- Create: `src/Flows/Engine/StepThroughExecutor.php`
- Modify: `src/Flows/Engine/FlowDispatcher.php` — accept `step_through: true` (canvas-only enforcement)
- Modify: `src/Api/Flows/Controllers/FlowRunController.php` — add `step($id)` action
- Modify: `src/Api/Flows/StudioFlowsApiRouteRegistrar.php` — register `POST /runs/{id}/step`
- Test: `tests/Feature/Flows/Engine/StepThroughExecutorTest.php`

- [ ] **Step 1: Write failing test**

```php
it('pauses after each operation and writes a resume token to the cache', function () {
    $run = (new FlowDispatcher())->dispatch($flow, [], ['step_through' => true, 'origin' => 'canvas']);

    expect($run->fresh()->status)->toBe('paused');
    expect(Cache::has("flow_step:{$run->id}"))->toBeTrue();
    expect($run->steps()->count())->toBe(1); // only the first op completed
});

it('advances exactly one step on POST /runs/{id}/step', function () {
    // run paused after step 1
    $this->withHeaders(['X-Api-Key' => $key->key])
        ->postJson("/api/studio/flows/runs/{$run->id}/step")
        ->assertOk();

    expect($run->fresh()->steps()->count())->toBe(2);
    expect($run->fresh()->status)->toBe('paused');
});

it('completes the run after the last step', function () {
    // advance until done; status should be "completed".
});

it('refuses step_through when origin is not canvas', function () {
    $this->expectException(InvalidArgumentException::class);
    (new FlowDispatcher())->dispatch($flow, [], ['step_through' => true, 'origin' => 'webhook']);
});

it('aborts on DELETE /runs/{id}/step (or POST with action=abort)', function () {
    $this->withHeaders(['X-Api-Key' => $key->key])
        ->postJson("/api/studio/flows/runs/{$run->id}/step", ['action' => 'abort'])
        ->assertOk();

    expect($run->fresh()->status)->toBe('aborted');
    expect(Cache::has("flow_step:{$run->id}"))->toBeFalse();
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

`StepThroughExecutor` keeps the in-progress `FlowContext` and the graph cursor inside a cache entry `flow_step:<run_id>` (serialized; default 30-minute TTL). On dispatch it runs op 1, persists state, marks the run `paused`. The `step` controller action loads the cache entry, invokes the next op, persists, returns the latest step. Action `abort` deletes the cache entry and marks the run `aborted`. Guard at the dispatcher: refuse `step_through=true` unless `origin === 'canvas'`.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): StepThroughExecutor for canvas test runs`

---

### Task 12: Canvas step-through UI

**Files:**
- Modify: `resources/js/flows/testrun/TestRunPanel.tsx` — "Step through" toggle + "Next step" + "Abort" buttons
- Modify: `resources/js/flows/state/*` (Zustand store) — track paused run id and last-completed node id
- Modify: `resources/js/flows/flow-canvas.tsx` (or relevant node renderer) — highlight last-completed node when paused
- Test: `resources/js/flows/__tests__/TestRunPanel.stepThrough.test.tsx`

- [ ] **Step 1: Write failing test**

```tsx
it('polls run state while paused and highlights the last-completed node', async () => {
    // Mock fetch: first poll returns { status: 'paused', last_step_node_id: 'op-1' }.
    // Assert node op-1 has data-step-status="completed" / highlight class.
});

it('calls POST /runs/{id}/step on "Next step"', async () => {
    fireEvent.click(screen.getByText('Next step'));
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringMatching(/\/runs\/.+\/step$/),
        expect.objectContaining({ method: 'POST' }));
});

it('aborts on "Abort" with action=abort', async () => {
    fireEvent.click(screen.getByText('Abort'));
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringMatching(/\/runs\/.+\/step$/),
        expect.objectContaining({ body: expect.stringContaining('"action":"abort"') }));
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

Add a `stepThrough: boolean` toggle alongside `dryRun`. When enabled, after dispatch poll `GET /api/studio/flows/runs/{id}` every 1s; when status is `paused`, expose `Next step` / `Abort` buttons and tell the Zustand store the `last_step_node_id` so the node renderer can apply a `data-step-completed` attribute. Stop polling on `completed` / `failed` / `aborted`.

- [ ] **Step 4: Run passing tests, build canvas bundle (`npm run build`), commit**

Commit: `feat(flows): canvas step-through UI with node highlight and polling`

---

## Surface 4 — Structured log export

### Task 13: Per-run export endpoint

**Files:**
- Modify: `src/Api/Flows/Controllers/FlowRunController.php` — add `export($id)`
- Modify: `src/Api/Flows/StudioFlowsApiRouteRegistrar.php` — `GET /runs/{id}/export`
- Create: `src/Api/Flows/Resources/RunExportResource.php` (or transformer)
- Test: `tests/Feature/Flows/Api/RunExportSingleRunTest.php`

- [ ] **Step 1: Write failing test**

```php
it('returns run metadata + step detail as JSON', function () {
    $this->withHeaders(['X-Api-Key' => $key->key])
        ->getJson("/api/studio/flows/runs/{$run->id}/export")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'flow_id', 'flow_version_id', 'status', 'started_at', 'finished_at',
                'duration_ms', 'trigger_type', 'trigger_payload',
                'steps' => [['id', 'operation_key', 'operation_type', 'attempt_number',
                              'status', 'input', 'output', 'error_class', 'error_message',
                              'started_at', 'finished_at']],
            ],
        ]);
});

it('masks sensitive values', function () {
    // Seed step input with {password: 'hunter2'}.
    $body = $this->withHeaders(['X-Api-Key' => $key->key])
        ->getJson("/api/studio/flows/runs/{$run->id}/export")->json();

    expect(json_encode($body))->not->toContain('hunter2');
});

it('is permission-gated and tenant-scoped', function () {
    // Wrong tenant key → 404.
    // Key without view_flows → 403.
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

`FlowRunController::export($id)` loads the run with `steps`, runs values through `LogMaskingService::mask()` (in case any data was written before Phase 8 masking was wired), and returns a `RunExportResource`. Permission check via existing `FlowPolicy::view`.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): per-run export endpoint`

---

### Task 14: Bulk export endpoint (streamed JSONL / CSV)

**Files:**
- Create: `src/Api/Flows/Controllers/FlowRunBulkExportController.php`
- Modify: `src/Api/Flows/StudioFlowsApiRouteRegistrar.php` — `GET /flows/{flow}/runs/export`
- Modify: `config/filament-studio.php` — `flows.export.max_range_days` (default 30)
- Test: `tests/Feature/Flows/Api/RunExportBulkJsonlTest.php`
- Test: `tests/Feature/Flows/Api/RunExportBulkCsvTest.php`

- [ ] **Step 1: Write failing tests**

```php
// RunExportBulkJsonlTest
it('streams runs as JSONL with one run per line', function () {
    // Seed 3 runs in range.
    $response = $this->withHeaders(['X-Api-Key' => $adminKey->key])
        ->get("/api/studio/flows/{$flow->id}/runs/export?format=jsonl&from=2026-05-01&to=2026-05-11");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/x-ndjson');

    $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
    expect($lines)->toHaveCount(3);
    foreach ($lines as $line) {
        expect(json_decode($line, true))->toHaveKeys(['id', 'steps']);
    }
});

it('paginates with started_at cursor without loading the full set into memory', function () {
    // Seed 5,000 runs; assert peak memory delta is bounded (use memory_get_peak_usage delta).
});

it('refuses ranges beyond the configured max (default 30 days)', function () {
    $this->withHeaders(['X-Api-Key' => $adminKey->key])
        ->getJson("/api/studio/flows/{$flow->id}/runs/export?format=jsonl&from=2025-01-01&to=2026-05-11")
        ->assertStatus(422);
});

it('is admin-only', function () {
    $this->withHeaders(['X-Api-Key' => $nonAdminKey->key])
        ->getJson("/api/studio/flows/{$flow->id}/runs/export?format=jsonl")
        ->assertForbidden();
});

// RunExportBulkCsvTest
it('streams CSV with run-level summary columns (no per-step detail)', function () {
    $response = $this->withHeaders(['X-Api-Key' => $adminKey->key])
        ->get("/api/studio/flows/{$flow->id}/runs/export?format=csv&from=...&to=...");

    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    $rows = str_getcsv($response->streamedContent());
    // header row + run rows
    expect(explode("\n", $response->streamedContent())[0])->toContain('id,flow_id,status,started_at,finished_at,duration_ms,trigger_type');
});
```

- [ ] **Step 2: Run failing tests**

- [ ] **Step 3: Implement**

Use `StreamedResponse`. Loop with `StudioFlowRun::query()->where('flow_id', …)->where('dry_run', false)->whereBetween('started_at', [from, to])->orderBy('started_at')->orderBy('id')->cursor()` so Eloquent yields rows lazily. For JSONL: emit one JSON-encoded line per run (including its eager-loaded steps, masked via `LogMaskingService`). For CSV: write header once, then a flattened summary row per run.

Validate `from`/`to` parse as ISO 8601 and the range is within `config('filament-studio.flows.export.max_range_days')`. Admin check via `FlowPolicy::export` (add the gate to the policy).

- [ ] **Step 4: Run passing tests, pint, commit**

Commit: `feat(flows): bulk run-export endpoint streaming JSONL/CSV`

---

### Task 15: Filament export action

**Files:**
- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/ListFlowRuns.php` — header action `Export runs`
- Test: `tests/Integration/Flows/Filament/ExportRunsActionTest.php`

- [ ] **Step 1: Write failing test**

```php
it('renders an Export runs header action with date-range and format selector', function () {
    $this->actingAs($this->makeUserWith(['view_flows'], adminRole: true));

    Livewire::test(ListFlowRuns::class, ['flow' => $flow->id])
        ->assertActionExists('exportRuns')
        ->callAction('exportRuns', ['from' => '2026-05-01', 'to' => '2026-05-11', 'format' => 'jsonl'])
        ->assertRedirect(); // download URL
});

it('hides the action for non-admin', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    Livewire::test(ListFlowRuns::class, ['flow' => $flow->id])
        ->assertActionHidden('exportRuns');
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

Filament header action with three form fields (`from`, `to`, `format`) and a submit that redirects to the bulk export URL (signed URL with the admin's API key, or routed through the panel auth). Visibility gated by `FlowPolicy::export`.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `feat(flows): Export runs action on ListFlowRuns`

---

## Wrap-up

### Task 16: Browser smoke test — canvas dry-run + step-through

**Files:**
- Create: `tests/Browser/Flows/CanvasDryRunAndStepThroughBrowserTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

it('runs a flow in dry-run mode without writing data and shows synthetic output', function () {
    $this->actingAs($admin);
    Http::fake(); // any real http would fail the assertion

    visit(route('filament.admin.resources.studio-flows.design', $flow))
        ->click('Test run')
        ->check('Dry run')
        ->click('Run')
        ->assertSee('dry-run-')
        ->assertNoJavaScriptErrors();

    expect(StudioRecord::count())->toBe($initialCount); // no side effects
    Http::assertNothingSent();
});

it('steps through a flow one operation at a time', function () {
    $this->actingAs($admin);

    visit(route('filament.admin.resources.studio-flows.design', $flow))
        ->click('Test run')
        ->check('Step through')
        ->click('Run')
        ->assertSee('Paused')
        ->click('Next step')
        ->assertSee('Step 2 of 3')
        ->click('Next step')
        ->assertSee('Completed')
        ->assertNoJavaScriptErrors();
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement (or wire up missing pieces)**

Most pieces ship in Tasks 9–12; this task is the final integration check. Fix any wiring issues uncovered here before continuing.

- [ ] **Step 4: Run passing test, commit**

Commit: `test(flows): browser smoke for canvas dry-run + step-through`

---

### Task 17: Phase10SmokeTest — end-to-end

**Files:**
- Create: `tests/Feature/Flows/Phase10SmokeTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

it('renders all four widgets without error against a seeded dataset', function () {
    seedPhase10Runs(); // helper that creates ~50 runs across 3 flows
    $this->actingAs($admin);

    Livewire::test(RecentFlowRunsWidget::class)->assertOk();
    Livewire::test(FlowFailureRateWidget::class)->assertOk();
    Livewire::test(TopFailingFlowsWidget::class)->assertOk();
    Livewire::test(FlowDurationWidget::class)->assertOk();
});

it('hits per-run export, bulk export, and replay endpoints end-to-end', function () {
    seedPhase10Runs();

    $this->withHeaders(['X-Api-Key' => $adminKey->key])
        ->getJson("/api/studio/flows/runs/{$someRun->id}/export")->assertOk();

    $this->withHeaders(['X-Api-Key' => $adminKey->key])
        ->get("/api/studio/flows/{$flow->id}/runs/export?format=jsonl&from=...&to=...")->assertOk();

    $this->withHeaders(['X-Api-Key' => $adminKey->key])
        ->postJson("/api/studio/flows/runs/{$someRun->id}/replay")->assertCreated();
});

it('excludes dry runs from widget queries by default', function () {
    // Seed both real + dry runs; assert dry runs absent from each widget.
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement seeding helper if needed**

A small `seedPhase10Runs()` helper in `tests/Feature/Flows/Helpers/Phase10Seed.php` that generates a mix of runs (success/failed/dry) with durations to exercise every widget.

- [ ] **Step 4: Run passing test, pint, commit**

Commit: `test(flows): phase 10 end-to-end smoke (widgets + export + replay)`

---

## Self-review

Before declaring the phase complete, verify the following:

- [ ] All schema changes match the design doc tables exactly (column names, types, defaults, indexes).
- [ ] Every test listed in the design doc's Testing table has a corresponding `it(...)` (RunDetailStepTimelineTest, RunDetailMaskedOutputTest, RunDetailRetryHistoryTest, RunDetailErrorStackPermissionTest, RunDetailReplayTest, ReplayBlockedWhenNoPublishedVersionTest, RecentFlowRunsWidgetTest, FlowFailureRateWidgetTest, TopFailingFlowsWidgetTest, FlowDurationWidgetTest, DryRunModeTest, StepThroughExecutorTest, RunExportSingleRunTest, RunExportBulkJsonlTest, RunExportBulkCsvTest, CanvasDryRunAndStepThroughBrowserTest).
- [ ] All four widgets exclude `dry_run = true` by default and respect tenant scope.
- [ ] Step-through is rejected for any `origin !== 'canvas'` — no real triggers can pause.
- [ ] Bulk export streams (verified by 5k-run memory test) and respects the max-range cap.
- [ ] Replay uses original payload against **current published version** and refuses cleanly when there is no current published version.
- [ ] Error stack trace is gated to admin role; non-admin only sees `error_class` + message.
- [ ] Sensitive values are masked in run-detail UI, per-run export, and bulk export (via Phase 8's `LogMaskingService`).
- [ ] Dry-run side-effect operations cover the full list: `http`, `email`, `notification`, `dispatch_job`, `fire_event`, `artisan`.
- [ ] `vendor/bin/pest --compact` is green; canvas Vitest suite is green; `vendor/bin/pint --dirty --format agent` produces no diff.
- [ ] No AI-attribution or Co-Authored-By lines in any commit on this branch.
- [ ] The branch is rebased on top of `release/flows-2.0` post-Phase-8 merge and contains no Phase 8 commits.
- [ ] Out-of-scope items (resume-from-failed-step, OpenTelemetry, alerting, custom user-defined widgets, WebSocket push) are **not** implemented — verify by inspection.
- [ ] Do **not** open a PR or merge — hand the branch back to the human reviewer.
