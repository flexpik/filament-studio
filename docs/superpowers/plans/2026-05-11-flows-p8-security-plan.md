# Phase 8 — Security & Permissions Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Webhooks safe by default (HMAC signing + replay protection + rate limiting); dangerous operations gated by permission; change-audit log on every flow lifecycle event.

**Architecture:** Role-based permissions extend the existing `StudioPermission` enum. A new `webhook_auth_mode` column on `studio_flows` selects between HMAC / api-key / none. The dangerous-op gate is enforced inside the Phase 7 `PublishFlowVersion` service. A new `studio_flow_audit_log` table is written by a model observer (lifecycle events) plus explicit `WriteFlowAuditLog` service calls from publish / rollback / secret-rotation paths. Sensitive-value masking is applied at run-step storage time (input + output) and to webhook trigger payloads before they are written to the run row.

**Tech Stack:** Laravel 12 / PHP 8.3+ / Filament v5 / Pest v4 / Livewire 4 / `spatie/laravel-permission` / Laravel `RateLimiter` / `encrypted` cast.

**Branch:** `feat/flows-p8-security` (cut from `release/flows-2.0` after Phase 7 merges).

**Depends on:** Phase 7 (Versioning) — relies on the new publish-flow lifecycle to hook the dangerous-op gate and audit-log events, and on `published_version_id` to identify the live trigger graph for webhook auth decisions.

**Working directory:** `/var/www/html/crud/packages/flexpik/filament-studio/`. All commits in the package repo. Author: `Serhii Fedorenko <drserhii@gmail.com>`. **Do not commit during plan execution unless the operator explicitly asks** — instead, push checkpoints to the branch when each task is green.

---

## Notes for the implementer

- The package's existing security primitives are minimal: `src/Flows/Security/HmacVerifier.php` (current impl uses `sha256=` prefix and the raw header — Phase 8 introduces a more strict `X-Studio-Signature` hex + `X-Studio-Timestamp` window protocol — extend, do not delete).
- Webhook entry point: `src/Api/Flows/Controllers/FlowWebhookController.php`, registered in `src/Api/Flows/StudioFlowsApiRouteRegistrar.php` at route name `studio.flows.webhook`.
- `StudioFlow` model already encrypts `webhook_secret`. Phase 8 adds `webhook_auth_mode` enum cast and `webhook_allowed_studio_api_key_ids` array cast.
- The existing `StudioPermission` enum already has flow CRUD cases (`view_flows`, `create_flows`, `update_flows`, `delete_flows`, `run_flows`). Phase 8 adds `publish_flow` and `run_dangerous_operations`.
- Existing operations live under `src/Flows/Operations/{Communication,Composition,Data,Logic}/`. The dangerous set per the spec: `HttpRequestActivity` (Communication) is dangerous when target host is not allow-listed; `TriggerFlowActivity` is **not** dangerous (composition); Dispatch Job / Fire Event / Call Artisan are dangerous if they exist or are added in later phases. For Phase 8, mark only the operation classes that actually exist in the tree as `DANGEROUS = true` and gate them; add a registry hook so future ops can opt in.
- Filament resource: `src/Flows/Filament/Resources/FlowResource.php` and pages under `FlowResource/Pages/`. Audit-log relation manager goes under `FlowResource/RelationManagers/`.
- Tests live at `tests/Feature/Flows/` (no Spatie) and `tests/Integration/Flows/` (Spatie). Permission-gated tests **must** go under `tests/Integration/Flows/Security/` so `SpatieTestCase` runs.
- The `studio_flow_runs` table is updated to redact at write time — coordinate with the Phase 1 `LogStep` job / `FlowRunRecorder` service (whichever finalizes step storage today) — search for the call site in `src/Flows/Engine/` and `src/Flows/Jobs/`.
- Each task follows the rhythm: write failing test → run-and-fail (capture failure output for the commit notes) → implement → run-and-pass → `vendor/bin/pint --dirty --format agent` → optional commit.

---

## Task 1: New permission enum cases + seeder grants

**Files:**
- Modify: `src/Enums/StudioPermission.php`
- Modify: `database/seeders/RolesAndPermissionsSeeder.php` (host app) — or, if the package owns the seeder, update the corresponding file under `database/seeders/` in the package
- Test: `tests/Integration/Flows/Security/FlowPermissionEnumTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\StudioPermission;

it('exposes publish_flow and run_dangerous_operations cases', function () {
    expect(StudioPermission::values())
        ->toContain('publish_flow')
        ->toContain('run_dangerous_operations');
});

it('grants publish_flow to studio-editor and studio-admin, dangerous to admin only', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $admin = \Spatie\Permission\Models\Role::findByName('studio-admin');
    $editor = \Spatie\Permission\Models\Role::findByName('studio-editor');

    expect($admin->hasPermissionTo('publish_flow'))->toBeTrue();
    expect($admin->hasPermissionTo('run_dangerous_operations'))->toBeTrue();
    expect($editor->hasPermissionTo('publish_flow'))->toBeTrue();
    expect($editor->hasPermissionTo('run_dangerous_operations'))->toBeFalse();
});
```

- [ ] **Step 2: Run the test, confirm it fails for the expected reason** (missing enum cases).
- [ ] **Step 3: Add `PublishFlow = 'publish_flow'` and `RunDangerousOperations = 'run_dangerous_operations'` cases to `StudioPermission`. Update the seeder to grant them per the matrix.**
- [ ] **Step 4: Re-run the test until green.**
- [ ] **Step 5: `vendor/bin/pint --dirty --format agent`.**
- [ ] **Step 6 (optional): Commit `feat(flows-p8): add publish_flow and run_dangerous_operations permissions`.**

---

## Task 2: Migration — alter `studio_flows` with auth-mode columns

**Files:**
- Create: `database/migrations/z_alter_studio_flows_add_webhook_auth_mode.php.stub`
- Test: `tests/Feature/Flows/Security/FlowsAuthModeColumnsMigrationTest.php`

- [ ] **Step 1: Write failing test** asserting `webhook_auth_mode` (string, default `'hmac'`) and `webhook_allowed_studio_api_key_ids` (json, nullable) exist on `studio_flows` after migration, and that `webhook_secret` remains encrypted at rest.

```php
use Illuminate\Support\Facades\Schema;

it('adds webhook_auth_mode and allowed_api_key_ids to studio_flows', function () {
    expect(Schema::hasColumn('studio_flows', 'webhook_auth_mode'))->toBeTrue();
    expect(Schema::hasColumn('studio_flows', 'webhook_allowed_studio_api_key_ids'))->toBeTrue();
});

it('defaults webhook_auth_mode to hmac', function () {
    $flow = \Flexpik\FilamentStudio\Flows\Models\StudioFlow::factory()->create();

    expect($flow->webhook_auth_mode->value)->toBe('hmac');
});
```

- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement the `.stub` migration using the `z_` prefix so it sorts after the create stub (mirrors `z_add_multilingual_columns.php.stub`).**
- [ ] **Step 4: Re-run, green. Pint. Optional commit.**

---

## Task 3: Migration — `studio_flow_audit_log` table

**Files:**
- Create: `database/migrations/create_studio_flow_audit_log_table.php.stub`
- Test: `tests/Feature/Flows/Security/FlowAuditLogTableMigrationTest.php`

- [ ] **Step 1: Failing test** asserting columns per spec (`id` uuid, `flow_id` uuid, `actor_id` nullable string, `actor_type` string, `event` string, `metadata` json, `ip_address` nullable string, `created_at`) and the `(flow_id, created_at)` index.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement the stub. Use the `config('filament-studio.table_prefix', 'studio_').'flow_audit_log'` pattern as in sibling stubs.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 4: `StudioFlowAuditLog` model + factory

**Files:**
- Create: `src/Flows/Models/StudioFlowAuditLog.php`
- Create: `database/factories/Flows/StudioFlowAuditLogFactory.php`
- Test: `tests/Feature/Flows/Models/StudioFlowAuditLogTest.php`

- [ ] **Step 1: Failing test**

```php
it('persists an audit-log row with json metadata', function () {
    $log = \Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog::factory()->create([
        'event' => 'published',
        'metadata' => ['version' => 3, 'change_summary' => 'add HTTP node'],
    ]);

    expect($log->metadata)->toBe(['version' => 3, 'change_summary' => 'add HTTP node']);
    expect($log->flow)->toBeInstanceOf(\Flexpik\FilamentStudio\Flows\Models\StudioFlow::class);
});
```

- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement model with `HasUuids`, `$guarded = ['id']`, cast `metadata => 'array'`, `belongsTo(StudioFlow::class, 'flow_id')`, table-name resolver mirroring sibling models. Add factory.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 5: `StudioFlow` model — new casts + relation

**Files:**
- Modify: `src/Flows/Models/StudioFlow.php`
- Create: `src/Flows/Enums/WebhookAuthMode.php` (enum: `Hmac`, `ApiKey`, `None`)
- Test: `tests/Feature/Flows/Models/StudioFlowAuthCastsTest.php`

- [ ] **Step 1: Failing test** verifying `webhook_auth_mode` casts to `WebhookAuthMode`, `webhook_allowed_studio_api_key_ids` casts to array (round-trip), and `auditLogs()` returns a `HasMany` of `StudioFlowAuditLog`.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Add the enum, casts, and `auditLogs()` relation.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 6: `StudioFlowObserver` — lifecycle audit rows

**Files:**
- Create: `src/Flows/Observers/StudioFlowObserver.php`
- Modify: `src/FilamentStudioServiceProvider.php` (register observer with `StudioFlow::observe(...)`)
- Test: `tests/Integration/Flows/Security/AuditLogObserverTest.php`

- [ ] **Step 1: Failing test**

```php
it('writes audit rows on created, updated and deleted', function () {
    $user = $this->makeUserWith(['view_flows', 'create_flows', 'update_flows', 'delete_flows']);
    $this->actingAs($user);

    $flow = \Flexpik\FilamentStudio\Flows\Models\StudioFlow::factory()->create();
    $flow->update(['description' => 'changed']);
    $flow->delete();

    $events = $flow->auditLogs()->withTrashed()->orderBy('created_at')->pluck('event')->all();
    expect($events)->toBe(['created', 'updated', 'deleted']);
    expect($flow->auditLogs()->first()->actor_id)->toBe((string) $user->getKey());
    expect($flow->auditLogs()->first()->actor_type)->toBe('user');
});
```

- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement observer. Extract actor from `auth()->user()` if present (use `user` type) else `system`. Capture `request()->ip()` when available. Store `getDirty()` (filtered to non-sensitive keys) in metadata for `updated`.**
- [ ] **Step 4: Register the observer in `FilamentStudioServiceProvider::boot()` alongside `RecordLifecycleObserver`.**
- [ ] **Step 5: Green. Pint. Optional commit.**

---

## Task 7: `WriteFlowAuditLog` service for explicit events

**Files:**
- Create: `src/Flows/Services/WriteFlowAuditLog.php`
- Test: `tests/Feature/Flows/Services/WriteFlowAuditLogTest.php`

- [ ] **Step 1: Failing test** that the service writes a row given `(StudioFlow $flow, string $event, array $metadata, ?Authenticatable $actor)` and the row has correct shape (event = `published`, metadata includes `version` and `change_summary`).
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement service. Single public method `write(StudioFlow, string, array, ?Authenticatable = null): StudioFlowAuditLog`. Pulls IP from request if container has it.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 8: `HmacWebhookVerifier` — strict signature + timestamp window

**Files:**
- Create: `src/Flows/Security/HmacWebhookVerifier.php`
- Create: `src/Flows/Security/Exceptions/InvalidWebhookSignatureException.php`
- Create: `src/Flows/Security/Exceptions/StaleWebhookTimestampException.php`
- Test: `tests/Feature/Flows/Security/HmacWebhookVerifierTest.php`

- [ ] **Step 1: Failing test** covering: (a) valid `X-Studio-Signature` (HMAC-SHA256 hex of raw body) + `X-Studio-Timestamp` within window → passes; (b) signature off by one char → throws `InvalidWebhookSignatureException`; (c) timestamp older than configured window → throws `StaleWebhookTimestampException`; (d) missing headers → invalid-signature exception; (e) constant-time compare (use `hash_equals`).

```php
beforeEach(fn () => config()->set('filament-studio.flows.webhook_timestamp_window_seconds', 300));

it('accepts a valid signature within the window', function () {
    $body = '{"foo":"bar"}';
    $secret = 'shhh';
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, $secret);

    $verifier = app(\Flexpik\FilamentStudio\Flows\Security\HmacWebhookVerifier::class);
    expect($verifier->verify($body, $sig, $ts, $secret))->toBeTrue();
});

it('rejects a stale timestamp', function () {
    $body = '{}';
    $secret = 'shhh';
    $ts = (string) (time() - 1000);
    $sig = hash_hmac('sha256', $ts.'.'.$body, $secret);

    $verifier = app(\Flexpik\FilamentStudio\Flows\Security\HmacWebhookVerifier::class);
    expect(fn () => $verifier->verify($body, $sig, $ts, $secret))
        ->toThrow(\Flexpik\FilamentStudio\Flows\Security\Exceptions\StaleWebhookTimestampException::class);
});
```

- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement verifier. Signed string is `"{timestamp}.{rawBody}"`. Throw the specific exceptions. Keep the existing `HmacVerifier` for backwards compatibility but mark it `@deprecated`.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 9: Webhook controller dispatches by `webhook_auth_mode`

**Files:**
- Modify: `src/Api/Flows/Controllers/FlowWebhookController.php`
- Test: `tests/Feature/Flows/Security/WebhookAuthModeDispatchTest.php`

- [ ] **Step 1: Failing test** posting to `route('studio.flows.webhook', $flow->slug)` for three flows (one per `webhook_auth_mode`), asserting:
  - `hmac`: missing `X-Studio-Signature` → 401; valid → 202.
  - `api_key`: missing `X-Api-Key` → 401; valid key → 202.
  - `none`: any payload → 202.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Refactor controller to switch on `$flow->webhook_auth_mode` and delegate to `HmacWebhookVerifier`, an api-key check, or no-op. Drop the legacy `bearer` branch (now superseded). The old controller still reads `auth_mode` from the trigger node — switch to reading the column on the flow row.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 10: API-key allowlist for webhook + `ValidateFlowApiKey` integration

**Files:**
- Modify: `src/Api/Flows/Controllers/FlowWebhookController.php`
- Modify: `src/Api/Middleware/ValidateFlowApiKey.php` (or extract a small `FlowApiKeyAllowlist` service if the middleware shouldn't know about flow-level scoping)
- Test: `tests/Feature/Flows/Security/WebhookApiKeyAllowlistTest.php`

- [ ] **Step 1: Failing test**

```php
it('rejects globally-valid api keys not on the flow allowlist', function () {
    $allowedKey = \Flexpik\FilamentStudio\Models\StudioApiKey::factory()->create();
    $rejectedKey = \Flexpik\FilamentStudio\Models\StudioApiKey::factory()->create();

    $flow = \Flexpik\FilamentStudio\Flows\Models\StudioFlow::factory()->create([
        'webhook_auth_mode' => 'api_key',
        'webhook_allowed_studio_api_key_ids' => [$allowedKey->id],
        'status' => 'active',
    ]);

    $this->postJson(route('studio.flows.webhook', $flow->slug), [], ['X-Api-Key' => $rejectedKey->key])
        ->assertStatus(403);

    $this->postJson(route('studio.flows.webhook', $flow->slug), [], ['X-Api-Key' => $allowedKey->key])
        ->assertStatus(202);
});
```

- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement: if `webhook_auth_mode = api_key` and allow-list is non-empty, validate the resolved key id against it before dispatch. Null/empty allow-list = any valid key passes.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 11: Public-mode admin gate (service guard + UI confirmation)

**Files:**
- Create: `src/Flows/Services/AssertCanEnablePublicWebhook.php`
- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/EditFlow.php` (UI: confirmation modal when switching to `none`)
- Test: `tests/Integration/Flows/Security/PublicWebhookGuardTest.php`

- [ ] **Step 1: Failing test** that:
  - Non-admin attempting to save `webhook_auth_mode = none` is rejected (form validation error / `AuthorizationException`).
  - Admin can save it (after confirmation acknowledgement).
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement the service guard (`assert(StudioFlow $flow, Authenticatable $actor): void` — throws if actor lacks the `studio-admin` role). Wire it from the form save handler; add a confirmation modal in Filament when `webhook_auth_mode` is changed to `none`.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 12: Webhook rate limiter (per-flow-per-IP)

**Files:**
- Modify: `src/FilamentStudioServiceProvider.php` (register `RateLimiter::for('studio-flow-webhook', ...)`)
- Modify: `src/Api/Flows/StudioFlowsApiRouteRegistrar.php` (apply `throttle:studio-flow-webhook` to the webhook route and to the manual-trigger HTTP route)
- Modify: `config/filament-studio.php` (add `flows.webhook_rate_limit_per_minute`)
- Test: `tests/Feature/Flows/Security/WebhookRateLimitTest.php`

- [ ] **Step 1: Failing test** that bursts 61 requests in a minute against `studio.flows.webhook` and asserts the 61st returns 429 with a `Retry-After` header.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement the limiter: `RateLimiter::for('studio-flow-webhook', fn (Request $req) => Limit::perMinute(config('filament-studio.flows.webhook_rate_limit_per_minute', 60))->by(($req->route('flowSlug') ?? '').'|'.$req->ip())->response(fn () => response()->json(['error' => 'rate_limited'], 429)->header('Retry-After', '60')));`. Apply via middleware.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 13: Dangerous-operation publish gate

**Files:**
- Modify: `src/Flows/Operations/Communication/HttpRequestActivity.php` — declare `public const DANGEROUS = true;` (with host allow-list check)
- Modify any further existing ops (DispatchJob/FireEvent/CallArtisan) **only if present** in the tree at implementation time
- Modify: `src/Flows/Services/PublishFlowVersion.php` — call new gate before publishing
- Create: `src/Flows/Services/AssertCanPublishDangerousGraph.php`
- Create: `src/Flows/Services/Exceptions/CannotPublishDangerousFlowException.php`
- Test: `tests/Integration/Flows/Security/DangerousOpPublishGateTest.php`

- [ ] **Step 1: Failing test**

```php
it('blocks editors from publishing flows containing a dangerous operation', function () {
    $editor = $this->makeUserWith(['publish_flow']);
    $admin = $this->makeUserWith(['publish_flow', 'run_dangerous_operations']);

    $flow = \Flexpik\FilamentStudio\Flows\Models\StudioFlow::factory()->create();
    $draft = $flow->versions()->create([
        'version' => 1,
        'graph' => [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'h', 'type' => 'operation', 'data' => ['operation' => 'http_request', 'config' => ['url' => 'https://evil.example']]],
            ],
            'edges' => [['source' => 't', 'target' => 'h']],
        ],
    ]);

    $service = app(\Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion::class);

    $this->actingAs($editor);
    expect(fn () => $service->publish($draft))
        ->toThrow(\Flexpik\FilamentStudio\Flows\Services\Exceptions\CannotPublishDangerousFlowException::class);

    $this->actingAs($admin);
    expect($service->publish($draft->fresh()))->not->toBeNull();
});
```

- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement: walk `graph.nodes`, resolve each operation via `OperationRegistry`, check the class constant; if any are `DANGEROUS` and the actor lacks `run_dangerous_operations`, throw. Also gate `publish_flow` permission at this step.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 14: `MasksSensitiveValues` utility for run-step storage

**Files:**
- Create: `src/Flows/Security/MasksSensitiveValues.php`
- Modify: the run-step writer (search `src/Flows/Engine/` and `src/Flows/Jobs/` for the call site that persists `input`/`output` JSON on `studio_flow_run_steps`)
- Modify: `config/filament-studio.php` (`flows.sensitive_key_patterns` default array)
- Test: `tests/Feature/Flows/Security/MasksSensitiveValuesTest.php` + `tests/Feature/Flows/Security/RunStepMaskingIntegrationTest.php`

- [ ] **Step 1: Failing test** for the utility (recursive walk, case-insensitive key match, defaults from spec) **and** an integration test that runs a flow step whose output contains `{"data": {"api_key": "abc"}}` and asserts the stored row has `"***"`.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement utility `mask(array $payload): array` with configurable regex list. Wire into the step persistence path so masking happens BEFORE the DB write.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 15: Per-flow webhook redact list

**Files:**
- Modify: `database/migrations/z_alter_studio_flows_add_webhook_auth_mode.php.stub` (extend Task 2 migration or add a follow-up stub adding `webhook_redact_paths` json nullable)
- Modify: `src/Flows/Models/StudioFlow.php` (cast)
- Modify: `src/Api/Flows/Controllers/FlowWebhookController.php` (apply redact list to `payload` before dispatching the run)
- Test: `tests/Feature/Flows/Security/WebhookRedactFieldsTest.php`

- [ ] **Step 1: Failing test** that posts a JSON body containing `{"user": {"password": "secret"}}` to a webhook with `webhook_redact_paths = ["/user/password"]` and asserts the resulting `studio_flow_runs.payload` does not contain `"secret"`.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Apply redact paths using a tiny JSON-pointer walker, **then** apply `MasksSensitiveValues` for defence-in-depth. Order matters — explicit list runs first.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 16: Rotate-webhook-secret action

**Files:**
- Create: `src/Flows/Services/RotateWebhookSecret.php`
- Modify: `src/Flows/Filament/Resources/FlowResource/Pages/EditFlow.php` — header action `Rotate webhook secret` (admin only)
- Test: `tests/Integration/Flows/Security/RotateWebhookSecretTest.php`

- [ ] **Step 1: Failing test** that:
  - Admin can invoke the action; old secret no longer verifies; new secret does.
  - Non-admin attempt → `AuthorizationException`.
  - An audit log row with event `webhook_secret_rotated` and `metadata.previous_secret_prefix` set to the first 4 chars of the old secret is created.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement service: generate new secret, persist, write audit log via `WriteFlowAuditLog`, return new secret for one-time display. Filament action displays the new secret in a modal once.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 17: Filament UI — audit-log tab, auth-mode select, allowlist multiselect, public-mode confirmation

**Files:**
- Create: `src/Flows/Filament/Resources/FlowResource/RelationManagers/AuditLogsRelationManager.php`
- Modify: `src/Flows/Filament/Resources/FlowResource.php` — register the relation manager, expose `webhook_auth_mode` select, `webhook_allowed_studio_api_key_ids` multiselect (visible only when mode = `api_key`), and the public-mode warning notice
- Test: `tests/Integration/Flows/Filament/FlowResourceSecurityFieldsTest.php`

- [ ] **Step 1: Failing test** that the edit form exposes the new fields, the api-key multiselect is hidden when mode != `api_key`, the audit-log tab is rendered, and a public-mode warning copy appears when mode = `none`.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Implement Filament components. Relation manager is read-only (no create/edit/delete actions) and paginated newest-first with infinite scroll.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 18: Config additions

**Files:**
- Modify: `config/filament-studio.php`
- Test: `tests/Feature/Flows/Security/FlowsConfigDefaultsTest.php`

Add a `flows` section:

```php
'flows' => [
    'webhook_rate_limit_per_minute' => env('STUDIO_FLOWS_WEBHOOK_RATE_LIMIT', 60),
    'webhook_timestamp_window_seconds' => env('STUDIO_FLOWS_WEBHOOK_TS_WINDOW', 300),
    'webhook_ip_allowlist' => [], // optional, applied across all flows
    'sensitive_key_patterns' => [
        '/password/i',
        '/token/i',
        '/secret/i',
        '/api[_-]?key/i',
        '/authorization/i',
        '/bearer/i',
    ],
],
```

- [ ] **Step 1: Failing test** asserting defaults are present and the env overrides work.
- [ ] **Step 2: Run-and-fail.**
- [ ] **Step 3: Add the config block.**
- [ ] **Step 4: Green. Pint. Optional commit.**

---

## Task 19: Phase 8 end-to-end smoke test

**Files:**
- Create: `tests/Integration/Flows/Phase8SmokeTest.php`

- [ ] **Step 1: Write the smoke spec covering, in one Pest file, each branch end-to-end:**
  - HMAC valid → 202 + run row created.
  - HMAC invalid signature → 401, no run row.
  - HMAC stale timestamp → 401, no run row.
  - Rate limit: 61st req in a minute → 429 with `Retry-After`.
  - Dangerous-op publish gate: editor blocked, admin allowed.
  - Webhook-secret rotation: old secret rejected, new accepted, audit row written.
  - Audit log captures the full lifecycle: `created`, `updated`, `published`, `webhook_secret_rotated`, `auth_mode_changed`, `deleted`.
  - Sensitive-key masking strips `api_key` from a stored step output.
  - Per-flow redact paths strip a designated body field from the stored trigger payload.
- [ ] **Step 2: Run-and-fail (all should already pass from earlier tasks; this catches regressions).**
- [ ] **Step 3: Tune any wiring leaks until green.**
- [ ] **Step 4: Pint.**
- [ ] **Step 5 (optional): Commit `test(flows-p8): add Phase 8 security smoke`.**

---

## Self-review checklist

Before opening the PR for `feat/flows-p8-security` → `release/flows-2.0`:

- [ ] All new tests pass: `vendor/bin/pest --compact tests/Feature/Flows/Security tests/Integration/Flows/Security tests/Integration/Flows/Phase8SmokeTest.php`.
- [ ] Full suite still passes (no regressions): `vendor/bin/pest --compact`.
- [ ] `vendor/bin/pint --dirty --format agent` reports no further changes.
- [ ] Larastan clean: `vendor/bin/phpstan analyse` (from host root).
- [ ] No plaintext secret ever hits the DB — confirmed via grep over migrations + a targeted DB-assert in `WebhookRedactFieldsTest` and `MasksSensitiveValuesTest`.
- [ ] All new permissions are seeded by `RolesAndPermissionsSeeder` and reflected in `database-permissions` output.
- [ ] All `RateLimiter::for(...)` registrations live in the service provider (not scattered).
- [ ] No Claude / AI / Co-Authored-By trailers on any commit. Author = `Serhii Fedorenko <drserhii@gmail.com>`.
- [ ] CHANGELOG entry drafted under the package `## [Unreleased]` heading describing Phase 8 additions.
- [ ] Branch is rebased onto the current tip of `release/flows-2.0` (Phase 7 merged) before PR.
- [ ] Manual smoke: spin up the dev server, create a flow with `webhook_auth_mode = hmac`, sign a request with `openssl dgst -sha256 -hmac $SECRET`, confirm 202 + run, then deliberately corrupt the signature and confirm 401.
- [ ] Manual smoke: log in as `studio-editor`, attempt to publish a flow with an `http_request` op → confirm `cannot_publish_dangerous_flow` surfaces in the UI. Switch to `studio-admin` and confirm publish succeeds.
