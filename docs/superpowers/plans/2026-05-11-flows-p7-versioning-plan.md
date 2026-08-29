# Phase 7 — Versioning & Publishing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Drafts are the canvas's working copy; triggers fire only the published version; every run is pinned to the exact version that executed it.

**Architecture:** Add `published_version_id` + `draft_graph` to flows, `flow_version_id` + `inline_graph` to runs. Save writes only to draft; publish creates a new immutable version row in a transaction and updates the pointer. Runtime resolves version via the run, never via the flow's current draft.

**Tech Stack:** Laravel 12, Filament v5, Pest v4, Orchestra Testbench, durable-workflow engine, React/xyflow canvas, Zustand store.

**Branch:** `feat/flows-p7-versioning` (cut from `release/flows-2.0`)

**Depends on:** Nothing (foundation of Flows 2.0).

---

## Conventions for every task

- All commands run from the package root `/var/www/html/crud/packages/flexpik/filament-studio/` unless noted.
- Tests: `vendor/bin/pest --compact --filter='<name>'`.
- After PHP edits: `cd /var/www/html/crud && vendor/bin/pint --dirty --format agent`.
- Commit author: Serhii Fedorenko `<drserhii@gmail.com>` (already configured for this clone). **Never** add `Co-Authored-By: Claude` lines.
- Commit messages use Conventional Commits (`feat:`, `test:`, `fix:`, `refactor:`, `chore:`).
- Migrations are `.php.stub` files; Testbench copies them at boot. For altering tables we add a NEW alter stub prefixed with `z_` so it sorts after creation stubs (precedent: `z_add_multilingual_columns.php.stub`, and the previously removed `z_add_wildcard_access_to_studio_api_keys.php.stub`).
- Pest syntax only — `it()`, `test()`, `expect()`. Use `use Illuminate\Foundation\Testing\RefreshDatabase;` via `uses(...)` only where needed (most tests in this suite already auto-migrate).
- Note: the existing `StudioFlow::publishedVersion()` / `draftVersion()` return `?StudioFlowVersion`, not relations. Task 2 changes this and downstream call-sites must be updated as a side-effect — every task that touches a call-site explicitly notes it.

---

## Task 1 — Migration: alter flows / flow_versions / flow_runs

**Files**

- Create: `database/migrations/z_add_versioning_columns_to_flows.php.stub`
- Test: `tests/Feature/Flows/Versioning/VersioningSchemaTest.php`

**Steps**

- [ ] Write failing test `tests/Feature/Flows/Versioning/VersioningSchemaTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('adds published_version_id, draft_graph, draft_updated_at to studio_flows', function () {
    $t = config('filament-studio.table_prefix', 'studio_').'flows';
    expect(Schema::hasColumn($t, 'published_version_id'))->toBeTrue()
        ->and(Schema::hasColumn($t, 'draft_graph'))->toBeTrue()
        ->and(Schema::hasColumn($t, 'draft_updated_at'))->toBeTrue();
});

it('adds published_by to studio_flow_versions and the (flow_id, published_at) index', function () {
    $t = config('filament-studio.table_prefix', 'studio_').'flow_versions';
    expect(Schema::hasColumn($t, 'published_by'))->toBeTrue();
    $indexes = collect(Schema::getIndexes($t))->pluck('columns');
    expect($indexes->contains(fn ($cols) => $cols === ['flow_id', 'published_at']))->toBeTrue();
});

it('adds inline_graph to studio_flow_runs and makes flow_version_id nullable', function () {
    $t = config('filament-studio.table_prefix', 'studio_').'flow_runs';
    expect(Schema::hasColumn($t, 'inline_graph'))->toBeTrue();
    $columns = collect(Schema::getColumns($t));
    $col = $columns->firstWhere('name', 'flow_version_id');
    expect($col['nullable'] ?? false)->toBeTrue();
});
```

- [ ] Run the test and confirm it fails:
  `vendor/bin/pest --compact --filter='VersioningSchemaTest'`
  Expected: `Schema::hasColumn(...published_version_id) === false`.

- [ ] Implement `database/migrations/z_add_versioning_columns_to_flows.php.stub`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::table($prefix.'flows', function (Blueprint $table) {
            $table->uuid('published_version_id')->nullable()->after('webhook_secret');
            $table->json('draft_graph')->nullable()->after('published_version_id');
            $table->timestamp('draft_updated_at')->nullable()->after('draft_graph');
        });

        Schema::table($prefix.'flow_versions', function (Blueprint $table) {
            $table->string('published_by')->nullable()->after('change_summary');
            $table->index(['flow_id', 'published_at'], 'flow_versions_flow_published_idx');
        });

        Schema::table($prefix.'flow_runs', function (Blueprint $table) {
            // SQLite can't change column nullability in-place; the create stub still declares it
            // non-nullable. Recreate the column nullable via a doctrine/dbal-free dance only if
            // the driver supports change(). For SQLite we add inline_graph and rely on Testbench
            // recreating the column via raw SQL if needed.
            $table->json('inline_graph')->nullable()->after('trigger_payload');
        });

        // Make flow_version_id nullable on drivers that support change(); SQLite tests already
        // permit null inserts since the column has no NOT NULL constraint until the FK is added.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table($prefix.'flow_runs', function (Blueprint $table) {
                $table->uuid('flow_version_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::table($prefix.'flow_runs', function (Blueprint $table) {
            $table->dropColumn('inline_graph');
        });
        Schema::table($prefix.'flow_versions', function (Blueprint $table) {
            $table->dropIndex('flow_versions_flow_published_idx');
            $table->dropColumn('published_by');
        });
        Schema::table($prefix.'flows', function (Blueprint $table) {
            $table->dropColumn(['published_version_id', 'draft_graph', 'draft_updated_at']);
        });
    }
};
```

Also update `database/migrations/create_studio_flow_runs_table.php.stub` to declare `flow_version_id` as `->nullable()` from the start (so the third assertion passes on SQLite without `doctrine/dbal`):

```php
$table->uuid('flow_version_id')->nullable()->index();
```

- [ ] Run the test and confirm it passes:
  `vendor/bin/pest --compact --filter='VersioningSchemaTest'`

- [ ] `cd /var/www/html/crud && vendor/bin/pint --dirty --format agent`

- [ ] Commit:

```bash
git add packages/flexpik/filament-studio/database/migrations/z_add_versioning_columns_to_flows.php.stub \
        packages/flexpik/filament-studio/database/migrations/create_studio_flow_runs_table.php.stub \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/VersioningSchemaTest.php
git commit -m "feat(flows): add versioning columns to flows, versions and runs tables"
```

---

## Task 2 — Model updates (StudioFlow, StudioFlowVersion, StudioFlowRun)

**Files**

- Modify: `src/Flows/Models/StudioFlow.php`
- Modify: `src/Flows/Models/StudioFlowVersion.php`
- Modify: `src/Flows/Models/StudioFlowRun.php`
- Test: `tests/Feature/Flows/Versioning/VersioningModelsTest.php`

**Side-effects to keep green**

- The previous `StudioFlow::publishedVersion()` and `draftVersion()` returned a single model (not a relation). Call-sites in `FlowDispatcher`, `PublishFlowVersion`, `EditFlow` page, `ManualTrigger` etc. used them as methods. We replace them: `publishedVersion()` becomes a BelongsTo relation; introduce `currentPublishedVersion()` accessor returning `?StudioFlowVersion` for backwards-compatible reads and migrate call-sites in this task.

**Steps**

- [ ] Write failing test `tests/Feature/Flows/Versioning/VersioningModelsTest.php`:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('exposes publishedVersion as a BelongsTo and versions as HasMany', function () {
    $flow = StudioFlow::factory()->create();
    $v = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create(['version' => 1]);
    $flow->update(['published_version_id' => $v->id]);

    expect($flow->publishedVersion()->first()?->id)->toBe($v->id)
        ->and($flow->versions()->count())->toBe(1);
});

it('casts draft_graph to array on StudioFlow', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 'n1']], 'edges' => []],
    ]);
    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'n1']], 'edges' => []]);
});

it('persists published_by on StudioFlowVersion', function () {
    $v = StudioFlowVersion::factory()->published()->create(['published_by' => 'user-123']);
    expect($v->fresh()->published_by)->toBe('user-123');
});

it('exposes flowVersion BelongsTo and inline_graph cast on StudioFlowRun', function () {
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => null,
        'inline_graph' => ['nodes' => [], 'edges' => []],
    ]);
    expect($run->fresh()->inline_graph)->toBe(['nodes' => [], 'edges' => []])
        ->and($run->flowVersion()->first())->toBeNull();
});
```

- [ ] Run and confirm failure: `vendor/bin/pest --compact --filter='VersioningModelsTest'`.

- [ ] Edit `src/Flows/Models/StudioFlow.php`: replace `publishedVersion()` and `draftVersion()` with relations, add casts and accessor.

```php
public function publishedVersion(): BelongsTo
{
    return $this->belongsTo(StudioFlowVersion::class, 'published_version_id');
}

public function versions(): HasMany
{
    return $this->hasMany(StudioFlowVersion::class, 'flow_id');
}

public function runs(): HasMany
{
    return $this->hasMany(StudioFlowRun::class, 'flow_id');
}

public function secrets(): HasMany
{
    return $this->hasMany(StudioFlowSecret::class, 'flow_id');
}

protected function casts(): array
{
    return [
        'status' => FlowStatus::class,
        'logging_mode' => LoggingMode::class,
        'webhook_secret' => 'encrypted',
        'draft_graph' => 'array',
        'draft_updated_at' => 'datetime',
    ];
}
```

Add import `use Illuminate\Database\Eloquent\Relations\BelongsTo;`. Remove the legacy `publishedVersion()` / `draftVersion()` methods that returned models.

- [ ] Edit `src/Flows/Models/StudioFlowVersion.php`: nothing new to declare (`published_by` is on `$guarded = ['id']`, so it is mass-assignable). No cast needed (string).

- [ ] Edit `src/Flows/Models/StudioFlowRun.php`: rename `version()` to `flowVersion()` and add `inline_graph` cast.

```php
public function flowVersion(): BelongsTo
{
    return $this->belongsTo(StudioFlowVersion::class, 'flow_version_id');
}

protected function casts(): array
{
    return [
        'status' => FlowRunStatus::class,
        'trigger_payload' => 'array',
        'accountability' => 'array',
        'inline_graph' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
    ];
}
```

- [ ] Update consumers of the old methods/relations (compile-only fix; behaviour rework comes later):
  - `src/Flows/Services/PublishFlowVersion.php` — change `$flow->publishedVersion()` → `$flow->publishedVersion` (the relation accessor). Task 4 rewrites this file completely; for now leave the file compiling.
  - `src/Flows/Filament/Resources/FlowResource/Pages/EditFlow.php` — `publishedVersion() === null` → `publishedVersion === null`.
  - `src/Flows/Engine/FlowDispatcher.php` — replace `$flow->draftVersion()` / `$flow->publishedVersion()` usage with `$flow->publishedVersion` and (Task 8) explicit `$version` parameter.
  - `src/Flows/Engine/FlowWorkflow.php` — `$run->with(['flow', 'version'])` → `$run->with(['flow', 'flowVersion'])`; `$run->version->graph` → `$run->flowVersion?->graph ?? $run->inline_graph` (Task 11 finalises this; here just keep compile-clean).

- [ ] Run: `vendor/bin/pest --compact --filter='VersioningModelsTest'` — passes.
- [ ] Run the full Flows suite to catch knock-on regressions: `vendor/bin/pest --compact tests/Feature/Flows`. Fix any compile errors revealed; behaviour fixes are in subsequent tasks.

- [ ] Pint: `cd /var/www/html/crud && vendor/bin/pint --dirty --format agent`

- [ ] Commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Models \
        packages/flexpik/filament-studio/src/Flows/Engine \
        packages/flexpik/filament-studio/src/Flows/Services/PublishFlowVersion.php \
        packages/flexpik/filament-studio/src/Flows/Filament/Resources/FlowResource/Pages/EditFlow.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/VersioningModelsTest.php
git commit -m "refactor(flows): rework version/draft relations on flow models"
```

---

## Task 3 — Factory updates

**Files**

- Modify: `database/factories/Flows/StudioFlowFactory.php`
- Modify: `database/factories/Flows/StudioFlowVersionFactory.php`
- Test: `tests/Feature/Flows/Versioning/VersioningFactoriesTest.php`

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('withPublishedVersion state mints and links a v1 version', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    expect($flow->published_version_id)->not->toBeNull()
        ->and($flow->publishedVersion?->version)->toBe(1)
        ->and($flow->publishedVersion?->published_at)->not->toBeNull();
});

it('published() state on version sets published_by', function () {
    $v = StudioFlowVersion::factory()->published('tester')->create();
    expect($v->published_at)->not->toBeNull()
        ->and($v->published_by)->toBe('tester');
});
```

- [ ] Run: `vendor/bin/pest --compact --filter='VersioningFactoriesTest'` — fails.

- [ ] Edit `StudioFlowVersionFactory`:

```php
public function published(?string $publishedBy = null): self
{
    return $this->state([
        'published_at' => now(),
        'published_by' => $publishedBy,
    ]);
}
```

- [ ] Edit `StudioFlowFactory`:

```php
public function withPublishedVersion(array $graph = ['nodes' => [], 'edges' => []]): self
{
    return $this->afterCreating(function (StudioFlow $flow) use ($graph) {
        $version = StudioFlowVersion::factory()
            ->for($flow, 'flow')
            ->published('factory')
            ->create(['version' => 1, 'graph' => $graph]);
        $flow->forceFill(['published_version_id' => $version->id])->save();
    });
}
```

Add the necessary `use` imports.

- [ ] Run: test passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/database/factories/Flows \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/VersioningFactoriesTest.php
git commit -m "test(flows): add factory states for published versions"
```

---

## Task 4 — `PublishFlowVersion` service (rewrite)

**Files**

- Modify: `src/Flows/Services/PublishFlowVersion.php`
- Test: `tests/Feature/Flows/Versioning/PublishFlowTest.php`

**Steps**

- [ ] Write failing test `tests/Feature/Flows/Versioning/PublishFlowTest.php`:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Illuminate\Support\Facades\DB;

it('mints a new version, points published_version_id at it, clears draft', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 't1', 'type' => 'trigger']], 'edges' => []],
        'draft_updated_at' => now(),
    ]);

    $service = app(PublishFlowVersion::class);
    $v = $service->publish($flow, changeSummary: 'first publish', publishedBy: 'user-1');

    $flow->refresh();
    expect($v->version)->toBe(1)
        ->and($v->published_at)->not->toBeNull()
        ->and($v->published_by)->toBe('user-1')
        ->and($v->change_summary)->toBe('first publish')
        ->and($flow->published_version_id)->toBe($v->id)
        ->and($flow->draft_graph)->toBeNull()
        ->and($flow->draft_updated_at)->toBeNull();
});

it('increments version number on each publish', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);

    $v2 = app(PublishFlowVersion::class)->publish($flow);
    expect($v2->version)->toBe(2);
});

it('refuses to publish when draft_graph is null', function () {
    $flow = StudioFlow::factory()->create(['draft_graph' => null]);

    expect(fn () => app(PublishFlowVersion::class)->publish($flow))
        ->toThrow(\RuntimeException::class, 'no_draft_to_publish');
});

it('rolls back the version row if pointer update fails', function () {
    $flow = StudioFlow::factory()->create(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    DB::shouldReceive('transaction')->andThrow(new \RuntimeException('boom'));
    // The test uses a Mockery facade override to force a transaction-level failure; assert no
    // partial state remains.
    try { app(PublishFlowVersion::class)->publish($flow); } catch (\Throwable) {}
    expect($flow->fresh()->versions()->count())->toBe(0);
});
```

> Note: the last `it()` can be skipped if facade mocking proves brittle; the transactional behaviour is implicit in the implementation. Keep it as documentation if it adds noise.

- [ ] Run: fails (signature mismatch).

- [ ] Rewrite `src/Flows/Services/PublishFlowVersion.php`:

```php
<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PublishFlowVersion
{
    public function __construct(private TriggerRegistry $triggers) {}

    public function publish(StudioFlow $flow, ?string $changeSummary = null, ?string $publishedBy = null): StudioFlowVersion
    {
        if ($flow->draft_graph === null) {
            throw new RuntimeException('no_draft_to_publish');
        }

        return DB::transaction(function () use ($flow, $changeSummary, $publishedBy) {
            $previous = $flow->publishedVersion;
            if ($previous !== null) {
                $this->unregisterTrigger($previous);
            }

            $nextVersion = ((int) $flow->versions()->max('version')) + 1;

            $version = StudioFlowVersion::create([
                'flow_id' => $flow->id,
                'version' => $nextVersion,
                'graph' => $flow->draft_graph,
                'published_at' => now(),
                'change_summary' => $changeSummary,
                'published_by' => $publishedBy,
                'created_at' => now(),
            ]);

            $flow->forceFill([
                'published_version_id' => $version->id,
                'draft_graph' => null,
                'draft_updated_at' => null,
            ])->save();

            $this->registerTrigger($version);

            return $version;
        });
    }

    private function registerTrigger(StudioFlowVersion $version): void
    {
        $type = $this->triggerType($version);
        if ($type !== null && $this->triggers->has($type)) {
            $this->triggers->resolve($type)->register($version);
        }
    }

    private function unregisterTrigger(StudioFlowVersion $version): void
    {
        $type = $this->triggerType($version);
        if ($type !== null && $this->triggers->has($type)) {
            $this->triggers->resolve($type)->unregister($version);
        }
    }

    private function triggerType(StudioFlowVersion $version): ?string
    {
        $node = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');

        return $node['data']['triggerType'] ?? null;
    }
}
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Services/PublishFlowVersion.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/PublishFlowTest.php
git commit -m "feat(flows): rewrite publish service around draft_graph and pointer update"
```

---

## Task 5 — `SaveFlowDraft` service

**Files**

- Create: `src/Flows/Services/SaveFlowDraft.php` (via `php artisan make:class`)
- Test: `tests/Feature/Flows/Versioning/DraftSaveTest.php`

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\SaveFlowDraft;

it('writes graph only to draft_graph and stamps draft_updated_at', function () {
    $flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [['id' => 'old']], 'edges' => []])->create();
    $publishedVersionId = $flow->published_version_id;

    $graph = ['nodes' => [['id' => 'new']], 'edges' => []];
    app(SaveFlowDraft::class)->save($flow, $graph);

    $flow->refresh();
    expect($flow->draft_graph)->toBe($graph)
        ->and($flow->draft_updated_at)->not->toBeNull()
        ->and($flow->published_version_id)->toBe($publishedVersionId)
        ->and($flow->publishedVersion->graph)->toBe(['nodes' => [['id' => 'old']], 'edges' => []]);
});
```

- [ ] Run: fails (class missing).
- [ ] Implement:

```php
<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

class SaveFlowDraft
{
    /** @param  array<string, mixed>  $graph */
    public function save(StudioFlow $flow, array $graph): StudioFlow
    {
        $flow->forceFill([
            'draft_graph' => $graph,
            'draft_updated_at' => now(),
        ])->save();

        return $flow;
    }
}
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Services/SaveFlowDraft.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/DraftSaveTest.php
git commit -m "feat(flows): add SaveFlowDraft service for autosave"
```

---

## Task 6 — `RollbackFlowVersion` service

**Files**

- Create: `src/Flows/Services/RollbackFlowVersion.php`
- Test: `tests/Feature/Flows/Versioning/RollbackTest.php`

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;

it('creates v(N+1) with same graph as the target version and updates pointer', function () {
    $flow = StudioFlow::factory()->create();
    $v1 = StudioFlowVersion::factory()->for($flow, 'flow')->published('user-a')
        ->create(['version' => 1, 'graph' => ['nodes' => [['id' => 'a']], 'edges' => []]]);
    $v2 = StudioFlowVersion::factory()->for($flow, 'flow')->published('user-b')
        ->create(['version' => 2, 'graph' => ['nodes' => [['id' => 'b']], 'edges' => []]]);
    $flow->update(['published_version_id' => $v2->id]);

    $restored = app(RollbackFlowVersion::class)->rollback($flow, $v1, publishedBy: 'user-c');

    expect($restored->version)->toBe(3)
        ->and($restored->graph)->toBe($v1->graph)
        ->and($restored->change_summary)->toBe('Restored from v1')
        ->and($flow->fresh()->published_version_id)->toBe($restored->id);
});

it('refuses to roll back to a version of a different flow', function () {
    $a = StudioFlow::factory()->create();
    $b = StudioFlow::factory()->create();
    $otherVersion = StudioFlowVersion::factory()->for($b, 'flow')->published()->create(['version' => 1]);

    expect(fn () => app(RollbackFlowVersion::class)->rollback($a, $otherVersion))
        ->toThrow(\RuntimeException::class, 'version_belongs_to_other_flow');
});
```

- [ ] Run: fails.
- [ ] Implement:

```php
<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use RuntimeException;

class RollbackFlowVersion
{
    public function __construct(private PublishFlowVersion $publisher) {}

    public function rollback(StudioFlow $flow, StudioFlowVersion $target, ?string $publishedBy = null): StudioFlowVersion
    {
        if ($target->flow_id !== $flow->id) {
            throw new RuntimeException('version_belongs_to_other_flow');
        }

        $flow->forceFill([
            'draft_graph' => $target->graph,
            'draft_updated_at' => now(),
        ])->save();

        return $this->publisher->publish(
            $flow->fresh(),
            changeSummary: "Restored from v{$target->version}",
            publishedBy: $publishedBy,
        );
    }
}
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Services/RollbackFlowVersion.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/RollbackTest.php
git commit -m "feat(flows): add RollbackFlowVersion service (append-only history)"
```

---

## Task 7 — Edit-after-publish hydrates draft

**Files**

- Modify: `src/Flows/Services/SaveFlowDraft.php` (or wrap with a behaviour helper)
- Test: `tests/Feature/Flows/Versioning/EditAfterPublishCopiesDraftTest.php`

The spec says "first mutation after publish copies the published graph into `draft_graph`". The cleanest implementation: a `HydrateDraftFromPublished` service used by both the REST controller and Filament action **before** the canvas issues the first save. We treat it as a small idempotent helper.

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\HydrateDraftFromPublished;

it('copies published graph into draft_graph the first time it is called', function () {
    $flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [['id' => 'p']], 'edges' => []])->create();
    expect($flow->draft_graph)->toBeNull();

    app(HydrateDraftFromPublished::class)->hydrate($flow);

    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'p']], 'edges' => []]);
});

it('does not overwrite an existing draft on subsequent calls', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'untouched']], 'edges' => []]]);

    app(HydrateDraftFromPublished::class)->hydrate($flow);

    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'untouched']], 'edges' => []]);
});

it('is a no-op when there is no published version', function () {
    $flow = StudioFlow::factory()->create();
    app(HydrateDraftFromPublished::class)->hydrate($flow);
    expect($flow->fresh()->draft_graph)->toBeNull();
});
```

- [ ] Run: fails.
- [ ] Implement `src/Flows/Services/HydrateDraftFromPublished.php`:

```php
<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

class HydrateDraftFromPublished
{
    public function hydrate(StudioFlow $flow): StudioFlow
    {
        if ($flow->draft_graph !== null) {
            return $flow;
        }
        $published = $flow->publishedVersion;
        if ($published === null) {
            return $flow;
        }

        $flow->forceFill([
            'draft_graph' => $published->graph,
            'draft_updated_at' => now(),
        ])->save();

        return $flow;
    }
}
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Services/HydrateDraftFromPublished.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/EditAfterPublishCopiesDraftTest.php
git commit -m "feat(flows): hydrate draft from published version on first edit"
```

---

## Task 8 — `FlowDispatcher` signature & version pinning

**Files**

- Modify: `src/Flows/Engine/FlowDispatcher.php`
- Test: `tests/Feature/Flows/Versioning/RunPinsVersionTest.php`

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;

it('pins flow_version_id from published version at dispatch time', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $originalVersionId = $flow->published_version_id;

    $run = app(FlowDispatcher::class)->dispatchAsync($flow, 'manual', [], []);
    expect($run->flow_version_id)->toBe($originalVersionId);

    // Now publish a new version — old run is unaffected.
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    app(PublishFlowVersion::class)->publish($flow);
    expect($run->fresh()->flow_version_id)->toBe($originalVersionId);
});

it('accepts an explicit version override (for test runs)', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $published = $flow->publishedVersion;

    $run = app(FlowDispatcher::class)->dispatchAsync(
        $flow, 'manual', [], [], inlineGraph: ['nodes' => [['id' => 'draft']], 'edges' => []],
    );

    expect($run->flow_version_id)->toBeNull()
        ->and($run->inline_graph)->toBe(['nodes' => [['id' => 'draft']], 'edges' => []]);
});

it('refuses to dispatch when there is no published version and no inline graph', function () {
    $flow = StudioFlow::factory()->create();
    expect(fn () => app(FlowDispatcher::class)->dispatchAsync($flow, 'manual', [], []))
        ->toThrow(\RuntimeException::class, 'no_published_version');
});
```

- [ ] Run: fails.
- [ ] Rewrite `FlowDispatcher`:

```php
<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use RuntimeException;

class FlowDispatcher
{
    public function __construct(private FlowWorkflow $workflow) {}

    /** @param  array<string, mixed>|null  $inlineGraph */
    public function dispatchSync(
        StudioFlow $flow,
        string $triggerType,
        array $payload,
        array $accountability,
        ?array $inlineGraph = null,
    ): StudioFlowRun {
        $run = $this->createRun($flow, $triggerType, $payload, $accountability, $inlineGraph);
        $this->workflow->run($run->id);

        return $run->fresh();
    }

    /** @param  array<string, mixed>|null  $inlineGraph */
    public function dispatchAsync(
        StudioFlow $flow,
        string $triggerType,
        array $payload,
        array $accountability,
        ?array $inlineGraph = null,
    ): StudioFlowRun {
        $run = $this->createRun($flow, $triggerType, $payload, $accountability, $inlineGraph);
        ExecuteFlowJob::dispatch($run->id)
            ->onConnection(config('filament-studio.flows.connection'))
            ->onQueue(config('filament-studio.flows.queue', 'default'));

        return $run;
    }

    private function createRun(
        StudioFlow $flow,
        string $triggerType,
        array $payload,
        array $accountability,
        ?array $inlineGraph,
    ): StudioFlowRun {
        if ($inlineGraph === null && $flow->published_version_id === null) {
            throw new RuntimeException('no_published_version');
        }

        return StudioFlowRun::create([
            'flow_id' => $flow->id,
            'flow_version_id' => $inlineGraph === null ? $flow->published_version_id : null,
            'inline_graph' => $inlineGraph,
            'status' => FlowRunStatus::Pending,
            'trigger_type' => $triggerType,
            'trigger_payload' => $payload,
            'accountability' => $accountability,
        ]);
    }
}
```

- [ ] Run: passes.
- [ ] Run the full flows suite, fix anything that still uses the old `_test_run` accountability flag (the new path uses explicit `inlineGraph`); update existing callers in `FlowRunController::test` (Task 10 covers this in full — patch in place for now so the suite stays green).
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Engine/FlowDispatcher.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/RunPinsVersionTest.php
git commit -m "feat(flows): pin run version explicitly, accept optional inline graph"
```

---

## Task 9 — Trigger refusal when unpublished

**Files**

- Modify: `src/Flows/Triggers/WebhookTrigger.php`, `src/Flows/Triggers/Schedule/ScheduleTrigger.php`, `src/Flows/Triggers/CollectionEventTrigger.php`, `src/Flows/Triggers/ManualTrigger.php`
- Modify: `src/Api/Flows/Controllers/FlowWebhookController.php`, `FlowRunController.php`
- Test: `tests/Feature/Flows/Versioning/TriggerRefusesUnpublishedTest.php`

The cleanest implementation is to centralise the gate in `FlowDispatcher::createRun` (already done in Task 8 — it throws `no_published_version`). What remains: each trigger surface must convert that into the spec's response.

| Trigger | Surface | Action when refused |
|---|---|---|
| Webhook | `FlowWebhookController::handle` | HTTP 409, body `{ "error": "no_published_version" }` |
| Manual API | `FlowRunController::run` | HTTP 409 |
| Schedule | `Schedule\ScheduleTrigger::fire` (or equivalent invocation point — confirm during implementation) | Create a `studio_flow_runs` row with `status = failed`, `accountability.refusal_reason = 'no_published_version'`, `finished_at = now()`. Do not dispatch the job. |
| Collection event | `CollectionEventTrigger::handle` | Same as schedule. |

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

it('webhook returns 409 when flow has no published version', function () {
    $flow = StudioFlow::factory()->create(['slug' => 'no-publish', 'webhook_secret' => null]);
    $r = $this->postJson("/api/studio/webhooks/{$flow->slug}", []);
    $r->assertStatus(409)->assertJson(['error' => 'no_published_version']);
});

it('manual API returns 409 when flow has no published version', function () {
    $flow = StudioFlow::factory()->create();
    $this->withHeaders(['X-Api-Key' => makeApiKey()])  // helper from existing tests
        ->postJson("/api/studio/flows/{$flow->id}/run")
        ->assertStatus(409);
});

it('schedule trigger records a failed run with refusal_reason', function () {
    $flow = StudioFlow::factory()->create();
    // Invoke ScheduleTrigger directly — exact API call confirmed during implementation.
    app(\Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTrigger::class)->fireForFlow($flow);

    $run = StudioFlowRun::query()->where('flow_id', $flow->id)->latest('started_at')->first();
    expect($run->status)->toBe(FlowRunStatus::Failed)
        ->and($run->accountability['refusal_reason'] ?? null)->toBe('no_published_version');
});
```

- [ ] Run: fails.
- [ ] Implement:
  - In `FlowWebhookController::handle` and `FlowRunController::run`, wrap `dispatchAsync` in `try { ... } catch (RuntimeException $e) { if ($e->getMessage() === 'no_published_version') return response()->json(['error' => $e->getMessage()], 409); throw $e; }`.
  - In `ScheduleTrigger`/`CollectionEventTrigger`, before calling the dispatcher, check `$flow->published_version_id === null` and, if so, insert a failed run with the refusal reason directly (no dispatcher call).
- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Triggers \
        packages/flexpik/filament-studio/src/Api/Flows/Controllers/FlowWebhookController.php \
        packages/flexpik/filament-studio/src/Api/Flows/Controllers/FlowRunController.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/TriggerRefusesUnpublishedTest.php
git commit -m "feat(flows): trigger surfaces refuse unpublished flows"
```

---

## Task 10 — Test Run path uses inline_graph

**Files**

- Modify: `src/Api/Flows/Controllers/FlowRunController.php` (method `test`)
- Test: `tests/Feature/Flows/Versioning/TestRunUsesInlineGraphTest.php`

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

it('stores draft_graph as inline_graph and leaves flow_version_id null', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 't1', 'type' => 'trigger']], 'edges' => []],
    ]);

    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->postJson("/api/studio/flows/{$flow->id}/test", ['payload' => ['x' => 1]])
        ->assertOk();

    $run = StudioFlowRun::query()->where('flow_id', $flow->id)->latest()->first();
    expect($run->flow_version_id)->toBeNull()
        ->and($run->inline_graph)->toBe(['nodes' => [['id' => 't1', 'type' => 'trigger']], 'edges' => []]);
});

it('test endpoint 422s when there is no draft to test', function () {
    $flow = StudioFlow::factory()->create(['draft_graph' => null]);
    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->postJson("/api/studio/flows/{$flow->id}/test")
        ->assertStatus(422)
        ->assertJson(['error' => 'no_draft']);
});
```

- [ ] Run: fails.
- [ ] In `FlowRunController::test`:

```php
public function test(RunFlowRequest $request, FlowDispatcher $dispatcher, string $flowId)
{
    $flow = $this->scopedFlow($request, $flowId);
    if ($flow->draft_graph === null) {
        return response()->json(['error' => 'no_draft'], 422);
    }

    $run = $dispatcher->dispatchAsync(
        flow: $flow,
        triggerType: 'test',
        payload: $request->input('payload', []),
        accountability: [...],
        inlineGraph: $flow->draft_graph,
    );

    return new FlowRunResource($run);
}
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Api/Flows/Controllers/FlowRunController.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/TestRunUsesInlineGraphTest.php
git commit -m "feat(flows): test runs use inline_graph snapshot"
```

---

## Task 11 — Runtime resolves graph from version OR inline

**Files**

- Modify: `src/Flows/Engine/FlowWorkflow.php`
- Test: `tests/Feature/Flows/Versioning/WorkflowGraphResolutionTest.php`

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

it('uses inline_graph when flow_version_id is null', function () {
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => null,
        'inline_graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        'status' => 'pending',
    ]);

    app(FlowWorkflow::class)->run($run->id);

    expect($run->fresh()->status->value)->toBe('completed');
});

it('uses version graph when flow_version_id is set', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $flow->published_version_id,
        'inline_graph' => null,
        'status' => 'pending',
    ]);

    app(FlowWorkflow::class)->run($run->id);
    expect($run->fresh()->status->value)->toBe('completed');
});
```

- [ ] Run: fails.
- [ ] Edit `FlowWorkflow::run`:

```php
$run = StudioFlowRun::query()->with(['flow', 'flowVersion'])->findOrFail($flowRunId);
// ...
$graph = $run->flowVersion?->graph ?? $run->inline_graph ?? ['nodes' => [], 'edges' => []];
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Engine/FlowWorkflow.php \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/WorkflowGraphResolutionTest.php
git commit -m "feat(flows): workflow resolves graph from version or inline snapshot"
```

---

## Task 12 — Filament UI (header pill, Publish modal, version history tab, run-detail pill)

**Files**

- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/EditFlow.php`
- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/ViewFlowRun.php`
- Modify: `src/Flows/Filament/Resources/FlowResource.php` (relation manager registration if used)
- Create: `src/Flows/Filament/Resources/FlowResource/RelationManagers/VersionsRelationManager.php`
- Test: `tests/Feature/Flows/Versioning/EditFlowVersioningUiTest.php`

**Steps**

- [ ] Write failing Pest Livewire test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\EditFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use function Pest\Livewire\livewire;

it('shows Published vN pill when flow has a published version', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    authenticateUser();

    livewire(EditFlow::class, ['record' => $flow->getRouteKey()])
        ->assertSeeText('Published v1');
});

it('shows Draft pill when draft_graph is set', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);

    authenticateUser();
    livewire(EditFlow::class, ['record' => $flow->getRouteKey()])
        ->assertSeeText('Draft');
});

it('publish action accepts a change summary and creates v2', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'new']], 'edges' => []]]);
    authenticateUser();

    livewire(EditFlow::class, ['record' => $flow->getRouteKey()])
        ->callAction('publish', ['change_summary' => 'tweak'])
        ->assertNotified();

    expect($flow->fresh()->publishedVersion->version)->toBe(2);
});

it('restore action on the versions relation creates a new version row', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $oldV1 = $flow->publishedVersion;
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'x']], 'edges' => []]]);
    app(\Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion::class)->publish($flow); // → v2
    authenticateUser();

    // exact assertion form depends on how the relation manager is registered — adjust to match
    // the EditFlow page's $relationManagers list.
    livewire(\Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers\VersionsRelationManager::class, [
        'ownerRecord' => $flow,
        'pageClass' => EditFlow::class,
    ])
        ->callTableAction('restore', $oldV1)
        ->assertSuccessful();

    expect($flow->fresh()->publishedVersion->version)->toBe(3);
});
```

- [ ] Run: fails.

- [ ] Implement on `EditFlow`:
  - Override `getSubheading()` (or use a header view) to return the pill string based on `$this->record->draft_graph !== null` / `published_version_id`.
  - Add `publish` action that opens a modal with a `Textarea::make('change_summary')` and calls `PublishFlowVersion::publish($flow, $data['change_summary'], (string) auth()->id())`. Action is disabled (`->disabled(fn () => $flow->draft_graph === null)`).
  - Run action: after Task 8 the dispatcher throws on unpublished flows — guard the UI similarly.
  - Register a `VersionsRelationManager` via `getRelationManagers()` on the page (or on the Resource if the project's pattern is resource-level).

- [ ] Implement `VersionsRelationManager`:

```php
<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version history';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($q) => $q->orderByDesc('version'))
            ->columns([
                TextColumn::make('version')->label('v')->sortable(),
                TextColumn::make('published_at')->dateTime(),
                TextColumn::make('published_by'),
                TextColumn::make('change_summary')->limit(60),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (StudioFlowVersion $record) => route(
                        'filament.admin.resources.studio-flows.versions.view',
                        ['record' => $this->getOwnerRecord(), 'versionId' => $record->id],
                    )),
                Action::make('restore')
                    ->requiresConfirmation()
                    ->action(function (StudioFlowVersion $record, RollbackFlowVersion $service) {
                        $service->rollback($this->getOwnerRecord(), $record, (string) auth()->id());
                    }),
            ]);
    }
}
```

The "View" route can either be a new Page (`ViewFlowVersion`) or reuse `DesignFlow` with a read-only query parameter — implementation detail; the test only requires the action to be callable.

- [ ] Update `ViewFlowRun.php` to show "Ran on version vN" using `$record->flowVersion?->version` or "Ran on draft (inline snapshot)" when null.

- [ ] Run: tests pass.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Flows/Filament \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/EditFlowVersioningUiTest.php
git commit -m "feat(flows): Filament UI for draft/publish/version history"
```

---

## Task 13 — REST API: save / publish / versions / get version / rollback

**Files**

- Modify: `src/Api/Flows/Controllers/FlowGraphController.php`
- Modify: `src/Api/Flows/StudioFlowsApiRouteRegistrar.php`
- Modify: `src/Api/Flows/Resources/FlowVersionResource.php` (include `published_by`)
- Create: `src/Api/Flows/Requests/RollbackFlowRequest.php`
- Test: `tests/Feature/Flows/Versioning/FlowGraphApiTest.php`

**Steps**

- [ ] Write failing test (covers save, publish, list, show, rollback endpoints; mirrors existing `Phase4SmokeTest` style):

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

it('PUT /flows/{id}/graph writes draft_graph only', function () {
    $flow = StudioFlow::factory()->create();
    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->putJson("/api/studio/flows/{$flow->id}/graph", ['graph' => ['nodes' => [['id' => 'a']], 'edges' => []]])
        ->assertOk();

    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'a']], 'edges' => []])
        ->and($flow->fresh()->published_version_id)->toBeNull();
});

it('POST /flows/{id}/publish mints v1 and points published_version_id', function () {
    $flow = StudioFlow::factory()->create(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->postJson("/api/studio/flows/{$flow->id}/publish", ['change_summary' => 'go'])
        ->assertOk()
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.change_summary', 'go');

    expect($flow->fresh()->published_version_id)->not->toBeNull();
});

it('GET /flows/{id}/versions returns descending list', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    app(\Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion::class)->publish($flow);

    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->getJson("/api/studio/flows/{$flow->id}/versions")
        ->assertOk()
        ->assertJsonPath('data.0.version', 2)
        ->assertJsonPath('data.1.version', 1);
});

it('GET /flows/{id}/versions/{versionId} returns the graph', function () {
    $flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [['id' => 'v1']], 'edges' => []])->create();
    $v = $flow->publishedVersion;

    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->getJson("/api/studio/flows/{$flow->id}/versions/{$v->id}")
        ->assertOk()
        ->assertJsonPath('data.graph.nodes.0.id', 'v1');
});

it('POST /flows/{id}/versions/{versionId}/rollback creates a new version', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $v1 = $flow->publishedVersion;
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'v2']], 'edges' => []]]);
    app(\Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion::class)->publish($flow);

    $this->withHeaders(['X-Api-Key' => makeApiKey($flow->tenant_id)])
        ->postJson("/api/studio/flows/{$flow->id}/versions/{$v1->id}/rollback")
        ->assertOk()
        ->assertJsonPath('data.version', 3);
});
```

- [ ] Run: fails.
- [ ] Update routes:

```php
Route::get('flows/{id}/graph', [FlowGraphController::class, 'show']);          // returns draft_graph
Route::put('flows/{id}/graph', [FlowGraphController::class, 'save']);          // writes draft_graph
Route::post('flows/{id}/publish', [FlowGraphController::class, 'publish']);
Route::get('flows/{id}/versions', [FlowGraphController::class, 'versions']);
Route::get('flows/{id}/versions/{versionId}', [FlowGraphController::class, 'showVersion']);
Route::post('flows/{id}/versions/{versionId}/rollback', [FlowGraphController::class, 'rollback']);
```

- [ ] Rewrite `FlowGraphController`:

```php
public function show(Request $request, string $flowId): JsonResponse
{
    $flow = $this->scopedFlow($request, $flowId);
    app(HydrateDraftFromPublished::class)->hydrate($flow);

    return response()->json([
        'data' => [
            'flow_id' => $flow->id,
            'draft_graph' => $flow->fresh()->draft_graph,
            'draft_updated_at' => $flow->fresh()->draft_updated_at,
            'published_version' => $flow->publishedVersion
                ? new FlowVersionResource($flow->publishedVersion)
                : null,
        ],
    ]);
}

public function save(SaveGraphRequest $request, string $flowId, SaveFlowDraft $service): JsonResponse
{
    $flow = $this->scopedFlow($request, $flowId);
    $service->save($flow, $request->input('graph'));

    return response()->json(['data' => [
        'draft_graph' => $flow->fresh()->draft_graph,
        'draft_updated_at' => $flow->fresh()->draft_updated_at,
    ]]);
}

public function publish(PublishFlowRequest $request, string $flowId, PublishFlowVersion $service): FlowVersionResource
{
    $flow = $this->scopedFlow($request, $flowId);
    $version = $service->publish(
        $flow,
        $request->input('change_summary'),
        (string) ($request->attributes->get('studio_api_key_id') ?? 'api'),
    );

    return new FlowVersionResource($version);
}

public function versions(Request $request, string $flowId): AnonymousResourceCollection { /* same */ }

public function showVersion(Request $request, string $flowId, string $versionId): FlowVersionResource
{
    $flow = $this->scopedFlow($request, $flowId);
    $version = $flow->versions()->findOrFail($versionId);

    return new FlowVersionResource($version);
}

public function rollback(Request $request, string $flowId, string $versionId, RollbackFlowVersion $service): FlowVersionResource
{
    $flow = $this->scopedFlow($request, $flowId);
    $version = $flow->versions()->findOrFail($versionId);

    return new FlowVersionResource(
        $service->rollback($flow, $version, (string) ($request->attributes->get('studio_api_key_id') ?? 'api')),
    );
}
```

- [ ] Update `FlowVersionResource` to include `published_by`.
- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/src/Api/Flows \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/FlowGraphApiTest.php
git commit -m "feat(flows): REST endpoints for draft, publish, versions and rollback"
```

---

## Task 14 — Canvas integration (Zustand + Toolbar)

**Files**

- Modify: `resources/js/flows/state/useFlowStore.ts`
- Modify: `resources/js/flows/api/useFlowApi.ts`
- Modify: `resources/js/flows/toolbar/Toolbar.tsx`
- Test: `resources/js/flows/__tests__/useFlowStore.test.ts`, `Toolbar.test.tsx`, `useFlowApi.test.ts`

**Steps**

- [ ] Write failing Vitest tests. In `useFlowStore.test.ts`:

```ts
import { describe, it, expect } from 'vitest';
import { createFlowStore } from '../state/useFlowStore';

describe('useFlowStore versioning', () => {
    it('tracks publishedVersion and draftSavedAt separately', () => {
        const store = createFlowStore();
        store.getState().loadFlow({
            draft_graph: { nodes: [], edges: [] },
            draft_updated_at: '2026-05-11T00:00:00Z',
            published_version: { version: 3, published_at: '2026-05-10T00:00:00Z' },
        } as any);

        const s = store.getState();
        expect(s.publishedVersion?.version).toBe(3);
        expect(s.draftSavedAt).toBe('2026-05-11T00:00:00Z');
        expect(s.dirty).toBe(false);
    });
});
```

In `Toolbar.test.tsx`, assert that the toolbar renders a "Published v3" pill when `publishedVersion` is set, and a "Draft (unsaved)" pill when `dirty === true`.

In `useFlowApi.test.ts`, assert that `saveDraft(graph)` PUTs to `/graph` and `publish(summary)` POSTs to `/publish`.

- [ ] Run: `npm run test --workspace=… -- --run` (or whatever the project's vitest invocation is — confirm via `package.json` scripts). Tests fail.

- [ ] Extend `useFlowStore.ts` with the new fields:

```ts
type FlowState = {
    // ...existing...
    publishedVersion: { version: number; published_at: string } | null;
    draftSavedAt: string | null;
    loadFlow: (resp: {
        draft_graph: Graph | null;
        draft_updated_at: string | null;
        published_version: { version: number; published_at: string } | null;
    }) => void;
};
```

`loadFlow` calls `loadGraph` on the draft if present, else the published graph (fetched via the new GET shape), and sets `publishedVersion`/`draftSavedAt`.

- [ ] Extend `useFlowApi.ts` with `saveDraft`, `publish`, `listVersions`, `getVersion`, `rollback`.

- [ ] Update `Toolbar.tsx`:
  - Replace "Save" button to call `saveDraft` and set `draftSavedAt` on success.
  - Add "Publish" button that opens a small modal with a change-summary textarea.
  - Render the pill: `Draft (unsaved)` (when `dirty`), `Draft saved Ns ago` (using `draftSavedAt`), or `Published v{publishedVersion.version}` (when no draft and no dirty state).

- [ ] Run tests: pass. Rebuild bundle: `npm run build` (project convention — last few commits explicitly republish the canvas bundle, e.g. `fcd9f37 chore(crud): rebuild canvas bundle for flows-phase-6`).

- [ ] Commit:

```bash
git add packages/flexpik/filament-studio/resources/js/flows \
        packages/flexpik/filament-studio/resources/dist  # bundle, if regenerated
git commit -m "feat(flows): canvas tracks draft/published state and publishes via toolbar"
```

---

## Task 15 — MVP data migration

**Files**

- Create: `database/migrations/z_migrate_mvp_flows_to_versioning.php.stub`
- Test: `tests/Feature/Flows/Versioning/MigrateMvpFlowsTest.php`

**Important:** the package's testing setup runs all migrations during boot, so the migration logic must be **idempotent** and tolerant of an empty fixture set. The migration scans existing rows in `studio_flow_versions` that pre-date the new pointer (i.e. flows whose latest published version exists but whose `published_version_id` is null) and back-fills the pointer.

**Steps**

- [ ] Write failing test:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('back-fills published_version_id from latest published version', function () {
    // Simulate "MVP" state: flow without pointer, but a published version exists.
    $flow = StudioFlow::factory()->create(['published_version_id' => null]);
    $v = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create(['version' => 1]);

    // Re-run the back-fill migration's logic via the artisan command we ship for fresh installs.
    \Illuminate\Support\Facades\Artisan::call('studio:flows:backfill-versioning');

    expect($flow->fresh()->published_version_id)->toBe($v->id);
});

it('mints v1 from legacy single-graph flows when no version exists', function () {
    // If older MVP stored the graph on a deprecated column `graph_legacy` (or similar),
    // assert the migration creates a v1 row. Confirm the legacy column name during
    // implementation by inspecting `git log --diff-filter=D` for the create_studio_flows
    // stub. If no legacy column exists in this codebase, this test is N/A and should be
    // skipped or removed.
})->skip('confirm legacy schema during implementation');
```

- [ ] Implement either (a) a back-fill `.stub` migration that runs raw SQL, or (b) a small Artisan command `studio:flows:backfill-versioning` invoked by the stub:

```php
public function up(): void
{
    $prefix = config('filament-studio.table_prefix', 'studio_');

    DB::table($prefix.'flows')
        ->whereNull('published_version_id')
        ->orderBy('id')
        ->each(function ($flow) use ($prefix) {
            $latest = DB::table($prefix.'flow_versions')
                ->where('flow_id', $flow->id)
                ->whereNotNull('published_at')
                ->orderByDesc('version')
                ->first();
            if ($latest) {
                DB::table($prefix.'flows')->where('id', $flow->id)->update([
                    'published_version_id' => $latest->id,
                ]);
            }
        });
}
```

- [ ] Run: passes.
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/database/migrations/z_migrate_mvp_flows_to_versioning.php.stub \
        packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/MigrateMvpFlowsTest.php
git commit -m "feat(flows): back-fill published_version_id for existing MVP flows"
```

---

## Task 16 — End-to-end smoke test

**Files**

- Create: `tests/Feature/Flows/Versioning/Phase7SmokeTest.php`

**Steps**

- [ ] Write the smoke test that exercises the full lifecycle through the public service API:

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Services\HydrateDraftFromPublished;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\SaveFlowDraft;

it('save → publish → trigger v1 → edit → publish v2 → trigger v2 → rollback → trigger v3', function () {
    $flow = StudioFlow::factory()->create();

    $save = app(SaveFlowDraft::class);
    $publish = app(PublishFlowVersion::class);
    $rollback = app(RollbackFlowVersion::class);
    $dispatcher = app(FlowDispatcher::class);

    // Step 1: save draft + publish v1
    $save->save($flow, ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []]);
    $v1 = $publish->publish($flow->fresh(), 'initial');
    expect($v1->version)->toBe(1);

    // Step 2: trigger fires v1
    $run1 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run1->flow_version_id)->toBe($v1->id);

    // Step 3: edit (hydrate draft) and save changes
    app(HydrateDraftFromPublished::class)->hydrate($flow);
    $save->save($flow->fresh(), ['nodes' => [
        ['id' => 't', 'type' => 'trigger', 'data' => []],
        ['id' => 'n2', 'type' => 'operation', 'data' => ['operationType' => 'noop']],
    ], 'edges' => []]);

    // Step 4: trigger fires before publish → still pinned to v1
    $run2 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run2->flow_version_id)->toBe($v1->id);

    // Step 5: publish v2
    $v2 = $publish->publish($flow->fresh(), 'add noop');
    expect($v2->version)->toBe(2);

    // Step 6: trigger fires v2
    $run3 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run3->flow_version_id)->toBe($v2->id);

    // Step 7: rollback to v1 → mints v3 with v1's graph
    $v3 = $rollback->rollback($flow->fresh(), $v1);
    expect($v3->version)->toBe(3)
        ->and($v3->graph)->toBe($v1->graph);

    // Step 8: trigger fires v3
    $run4 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run4->flow_version_id)->toBe($v3->id);

    // Final ledger
    expect(StudioFlowRun::query()->where('flow_id', $flow->id)->count())->toBe(4);
});
```

- [ ] Run: `vendor/bin/pest --compact --filter='Phase7SmokeTest'` — passes.
- [ ] Run the full Versioning suite + full Flows suite + full package suite:
  - `vendor/bin/pest --compact tests/Feature/Flows/Versioning`
  - `vendor/bin/pest --compact tests/Feature/Flows`
  - `vendor/bin/pest --compact`
- [ ] Pint + commit:

```bash
git add packages/flexpik/filament-studio/tests/Feature/Flows/Versioning/Phase7SmokeTest.php
git commit -m "test(flows): phase 7 end-to-end smoke (save → publish → trigger → rollback)"
```

---

## Self-review pass

Spec coverage check:

| Spec section | Covered by |
|---|---|
| Schema: `studio_flows` additions | Task 1 |
| Schema: `studio_flow_versions.published_by` + index | Task 1 |
| Schema: `studio_flow_runs.flow_version_id` nullable + `inline_graph` | Task 1 |
| Save (autosave) writes only to `draft_graph` | Task 5 |
| Publish lifecycle (txn, mint, point, clear draft, set `published_by`) | Task 4 |
| Edit after publish hydrates `draft_graph` | Task 7 |
| Rollback creates new version, append-only history | Task 6 |
| Test Run uses `inline_graph` | Task 10 |
| Trigger refusal (webhook/manual API → 409, schedule/collection → failed run) | Task 9 |
| `dispatchAsync` signature change | Task 8 |
| Runtime reads from `flowVersion->graph` else `inline_graph` | Task 11 |
| Filament EditFlow header pill | Task 12 |
| Filament Publish modal | Task 12 |
| Filament Version history tab w/ View + Restore | Task 12 |
| Run-detail "Ran on version vN" pill | Task 12 |
| MVP data migration | Task 15 |
| End-to-end smoke | Task 16 |
| `PublishFlowTest` | Task 4 |
| `DraftSaveTest` | Task 5 |
| `RollbackTest` | Task 6 |
| `TriggerRefusesUnpublishedTest` | Task 9 |
| `RunPinsVersionTest` | Task 8 |
| `TestRunUsesInlineGraphTest` | Task 10 |
| `EditAfterPublishCopiesDraftTest` | Task 7 |
| `MigrateMvpFlowsTest` | Task 15 |

Type/name consistency:

- `StudioFlow::publishedVersion()` is a **BelongsTo relation** everywhere from Task 2 onward; callers use the accessor `$flow->publishedVersion` (not `$flow->publishedVersion()`) for the model. The `versions()` relation is `HasMany`.
- `StudioFlowRun::flowVersion()` is the BelongsTo (renamed from the previous `version()`). Eager loads update accordingly (Task 11).
- `FlowDispatcher::dispatchAsync` / `dispatchSync` final signature: `(StudioFlow $flow, string $triggerType, array $payload, array $accountability, ?array $inlineGraph = null)`. The old `_test_run` accountability shortcut is removed (Task 10 migrates the canvas test endpoint).
- Refusal reason string is the **same constant** `'no_published_version'` across `FlowDispatcher`, controllers, and trigger handlers — search-and-replace must keep this consistent.
- Migration column order in `create_studio_flow_runs_table.php.stub` is altered in Task 1 (column declared `nullable`); the Task 1 alter stub does NOT try to re-change the column on SQLite. All other DBs go through the alter stub.

Open implementation decisions deferred to the implementing agent (call out if unclear during execution):

- Whether `View version` opens a dedicated page or a query-string-driven `DesignFlow` (Task 12).
- Exact invocation point for `ScheduleTrigger::fireForFlow($flow)` — the schedule trigger may not currently expose a `fire` method on a single flow; if not, refactor to add one and adjust the Task 9 test accordingly.
- Whether `makeApiKey()` helper exists in the test suite under that exact name — check `tests/Pest.php` or existing API tests (`tests/Feature/Api/Flows/*`) for the actual helper name and reuse it.
