# Phase 9 — Extensibility API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Third-party packages can register flow operations and triggers via plugin-style facade calls in their service provider. PHP-side only — operation logic + JSON-schema config rendered by the existing canvas FieldRenderer.

**Architecture:** Promote internal `TriggerRegistry` and `OperationRegistry` (the existing `FlowOperationRegistry` from Phase 2 work — actual class is `Flexpik\FilamentStudio\Flows\Operations\OperationRegistry`) to a public facade API. Freeze public contracts under `Flexpik\FilamentStudio\Contracts\Flows\` (semver). Compile-time validation at publish ensures unknown keys and invalid configs fail clearly. Built-in operations are refactored to implement the new public contracts — proven by an arch test.

**Tech Stack:** Laravel 12, Pest v4 (with arch tests), Filament v5, durable-workflow.

**Branch:** `feat/flows-p9-extensibility` (cut from `release/flows-2.0` after Phase 8 merges)

**Depends on:** Phase 7 (publish-time validation hook), Phase 8 (dangerous-op gate hook).

**Working directory:** `/var/www/html/crud/packages/flexpik/filament-studio/`. All commits land in the **package repo**. Author: `Serhii Fedorenko <drserhii@gmail.com>` — never add AI attribution to commits.

---

## Notes for the implementer

- The existing internal registry is named `OperationRegistry` (`src/Flows/Operations/OperationRegistry.php`), not `FlowOperationRegistry`. Keep its file location; only its public surface is widened. Optionally alias as `FlowOperationRegistry` via a final wrapper class if it improves API clarity — discuss in code review.
- The existing internal contract `Flexpik\FilamentStudio\Flows\Operations\FlowOperationActivity` has signature `execute(array $config, FlowContext $context): mixed`. The new public contract `Flexpik\FilamentStudio\Contracts\Flows\FlowOperation` uses `execute(OperationContext $context): OperationResult`. The OperationRegistry MUST support both contracts during the migration (Tasks 5–8) and only the new one after Task 13 (arch test enforces).
- The existing `FlowWorkflow` already template-resolves config before calling activities. Preserve this behavior — `OperationContext::config()` returns the already-resolved config; `OperationContext::interpolate()` is for runtime calls within an operation against raw template strings the op stores itself.
- Tests for each refactored operation should use `OperationContext` factories directly (not full workflow) for speed — except where behavior is workflow-mediated (branching, masking, audit trail).
- Use `Mail::fake()`, `Http::fake()`, `Notification::fake()`, `Log::shouldReceive()` in tests where relevant.
- After every PHP file change, run `docker exec php83 /var/www/html/crud/vendor/bin/pint --dirty --format agent` before committing.
- After every test/code change, run `vendor/bin/pest --compact` (full suite) before committing the task. If a task touches only a single file's tests, an intermediate `vendor/bin/pest --filter="..."` is acceptable but the final pre-commit must be the full suite.

---

## Task 1: Public contracts under `Contracts\Flows\`

**Files:**
- Create: `src/Contracts/Flows/FlowOperation.php`
- Create: `src/Contracts/Flows/FlowOperationConfig.php`
- Create: `src/Contracts/Flows/FlowTrigger.php`
- Create: `src/Contracts/Flows/FlowTriggerConfig.php`
- Create: `src/Flows/Exceptions/InvalidOperationConfigException.php`
- Test: `tests/Unit/Flows/Contracts/PublicContractsExistTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger as PublicFlowTrigger;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

it('exposes FlowOperation public contract', function () {
    expect(interface_exists(FlowOperation::class))->toBeTrue();
    $rc = new ReflectionClass(FlowOperation::class);
    expect($rc->hasMethod('execute'))->toBeTrue();
    $m = $rc->getMethod('execute');
    expect($m->getNumberOfParameters())->toBe(1);
    expect((string) $m->getReturnType())->toContain('OperationResult');
});

it('exposes FlowOperationConfig public contract', function () {
    $rc = new ReflectionClass(FlowOperationConfig::class);
    foreach (['schema', 'defaults', 'validate'] as $method) {
        expect($rc->hasMethod($method))->toBeTrue();
    }
});

it('exposes FlowTrigger public contract with register/unregister', function () {
    $rc = new ReflectionClass(PublicFlowTrigger::class);
    expect($rc->hasMethod('register'))->toBeTrue();
    expect($rc->hasMethod('unregister'))->toBeTrue();
});

it('exposes FlowTriggerConfig public contract', function () {
    expect(interface_exists(FlowTriggerConfig::class))->toBeTrue();
});
```

- [ ] **Step 2: Run failing test** — `vendor/bin/pest --filter=PublicContractsExist`

- [ ] **Step 3: Implement the four interfaces**

Place each under `Flexpik\FilamentStudio\Contracts\Flows`. `FlowTrigger` public contract has the same shape as the existing internal one (`register(StudioFlowVersion)`, `unregister(StudioFlowVersion)`). `FlowOperation::execute` typehints `OperationContext` and returns `OperationResult` (defined in Tasks 2 and 3 — declare without importing; PHP allows forward reference inside the same namespace via FQCN). `FlowOperationConfig::validate(array $config): void` may throw `InvalidOperationConfigException` (create as a `\RuntimeException` subclass with `errors(): array<string,string>`).

- [ ] **Step 4: Run tests** — full suite via `vendor/bin/pest --compact`.

- [ ] **Step 5: Pint + commit**

```
feat(flows): introduce Contracts\Flows public namespace (P9 task 1)
```

---

## Task 2: `OperationContext` immutable public class + `DataChain` value object

**Files:**
- Create: `src/Contracts/Flows/OperationContext.php`
- Create: `src/Contracts/Flows/DataChain.php`
- Test: `tests/Unit/Flows/Contracts/OperationContextTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\DataChain;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

it('exposes accessors and is immutable', function () {
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow)->create();
    $chain = new DataChain(
        trigger: ['email' => 'a@b.test'],
        outputs: ['fetch_user' => ['id' => 42]],
        last: ['id' => 42],
    );

    $ctx = new OperationContext(
        flow: $flow,
        run: $run,
        dataChain: $chain,
        config: ['to' => 'a@b.test'],
        tenantId: 'tenant-1',
    );

    expect($ctx->flow()->is($flow))->toBeTrue();
    expect($ctx->run()->is($run))->toBeTrue();
    expect($ctx->tenantId())->toBe('tenant-1');
    expect($ctx->config())->toBe(['to' => 'a@b.test']);
    expect($ctx->dataChain()->trigger())->toBe(['email' => 'a@b.test']);
    expect($ctx->dataChain()->last())->toBe(['id' => 42]);
    expect($ctx->dataChain()->get('fetch_user'))->toBe(['id' => 42]);
    expect($ctx->dataChain()->get('missing'))->toBeNull();
});

it('interpolates references against the data chain', function () {
    // Build a real ctx via factory helper below.
    $ctx = makeOperationContext(
        trigger: ['user' => ['name' => 'Ada']],
        outputs: ['load' => ['email' => 'ada@lovelace.dev']],
    );

    expect($ctx->interpolate('Hello {{ $trigger.user.name }}'))
        ->toBe('Hello Ada');
    expect($ctx->interpolate('{{ $load.email }}'))
        ->toBe('ada@lovelace.dev');
});

it('readonly properties prevent mutation', function () {
    $ctx = makeOperationContext();
    $rc = new ReflectionClass($ctx);
    foreach ($rc->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
```

Add a small `makeOperationContext()` helper to `tests/Pest.php` (or a `tests/Helpers/FlowFactories.php` file autoloaded via composer dev-autoload).

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

`DataChain`: readonly class with `trigger()`, `last()`, `get(string $opKey)`, and `toArray()` (for templating bridge).

`OperationContext`: readonly class with promoted public-readonly properties for flow/run/dataChain/config/tenantId. `interpolate()` delegates to existing `Flexpik\FilamentStudio\Flows\Engine\Templating\TemplateEngine` resolved from the container, feeding it `$dataChain->toArray()`.

- [ ] **Step 4: Run tests**

- [ ] **Step 5: Pint + commit**

```
feat(flows): add OperationContext + DataChain public classes (P9 task 2)
```

---

## Task 3: `OperationResult` public class

**Files:**
- Create: `src/Contracts/Flows/OperationResult.php`
- Test: `tests/Unit/Flows/Contracts/OperationResultTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

it('constructs a success result', function () {
    $r = OperationResult::success(['id' => 1]);
    expect($r->isSuccess())->toBeTrue();
    expect($r->isFailure())->toBeFalse();
    expect($r->output())->toBe(['id' => 1]);
    expect($r->branch())->toBeNull();
});

it('constructs a failure result preserving previous throwable', function () {
    $e = new RuntimeException('nope');
    $r = OperationResult::fail('something broke', $e);
    expect($r->isFailure())->toBeTrue();
    expect($r->message())->toBe('something broke');
    expect($r->previous())->toBe($e);
});

it('constructs a branch result for condition / switch ops', function () {
    $r = OperationResult::branch('failure', ['reason' => 'too small']);
    expect($r->isSuccess())->toBeTrue();
    expect($r->branch())->toBe('failure');
    expect($r->output())->toBe(['reason' => 'too small']);
});

it('is immutable / readonly', function () {
    $rc = new ReflectionClass(OperationResult::class);
    foreach ($rc->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement** — three private constructor args (status enum, branch?, output, message?, previous?), three static factories.

- [ ] **Step 4: Run tests + Step 5: Pint + commit**

```
feat(flows): add OperationResult public class (P9 task 3)
```

---

## Task 4: `FilamentStudio` facade with `registerFlowOperation` / `registerFlowTrigger`

**Files:**
- Create: `src/Facades/FilamentStudio.php`
- Create: `src/Support/FilamentStudioManager.php` (the facade's underlying singleton — delegates to the two registries)
- Modify: `src/FilamentStudioServiceProvider.php` (bind manager singleton)
- Test: `tests/Feature/Flows/Extensibility/FacadeRegistrationTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Facades\FilamentStudio;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Tests\Fixtures\Flows\FakeSlackOperation;
use Tests\Fixtures\Flows\FakeSlackOperationConfig;
use Tests\Fixtures\Flows\FakeStripeTrigger;
use Tests\Fixtures\Flows\FakeStripeTriggerConfig;

it('registers a flow operation through the facade', function () {
    FilamentStudio::registerFlowOperation(
        key: 'send_slack_message',
        label: 'Send Slack Message',
        icon: 'heroicon-o-chat-bubble-left',
        group: 'communication',
        activity: FakeSlackOperation::class,
        configSchema: FakeSlackOperationConfig::class,
        dangerous: false,
    );

    $registry = app(OperationRegistry::class);
    expect($registry->has('send_slack_message'))->toBeTrue();
    expect($registry->labelFor('send_slack_message'))->toBe('Send Slack Message');
    expect($registry->resolve('send_slack_message'))->toBeInstanceOf(FakeSlackOperation::class);
    expect($registry->configFor('send_slack_message'))->toBe(FakeSlackOperationConfig::class);
    expect($registry->isDangerous('send_slack_message'))->toBeFalse();
    expect($registry->groupFor('send_slack_message'))->toBe('communication');
});

it('registers a flow trigger through the facade', function () {
    FilamentStudio::registerFlowTrigger(
        key: 'stripe_webhook',
        label: 'Stripe Webhook',
        icon: 'heroicon-o-credit-card',
        handler: FakeStripeTrigger::class,
        configSchema: FakeStripeTriggerConfig::class,
    );

    $registry = app(TriggerRegistry::class);
    expect($registry->has('stripe_webhook'))->toBeTrue();
    expect($registry->resolve('stripe_webhook'))->toBeInstanceOf(FakeStripeTrigger::class);
});
```

Create the four fixtures under `tests/Fixtures/Flows/`. Each fixture implements the public contracts and returns trivial values.

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement**

`FilamentStudioManager` exposes `registerFlowOperation(...)` and `registerFlowTrigger(...)` and proxies to the two registries (calling the new registry signatures from Tasks 5–6). Facade is a thin `Illuminate\Support\Facades\Facade` whose accessor is the manager. Service provider binds the manager as singleton in `packageRegistered()`.

- [ ] **Step 4: Run tests + Step 5: Pint + commit**

```
feat(flows): add FilamentStudio facade with operation/trigger registration (P9 task 4)
```

---

## Task 5: Promote `OperationRegistry` to the public registration surface

**Files:**
- Modify: `src/Flows/Operations/OperationRegistry.php`
- Create: `src/Flows/Exceptions/DuplicateFlowOperationException.php`
- Modify: `src/FilamentStudioServiceProvider.php` (update built-in registrations to use new signature)
- Test: `tests/Unit/Flows/Operations/OperationRegistryTest.php`

The new registry shape:

```php
public function register(
    string $key,
    string $label,
    string $activity,
    ?string $configSchema = null,
    ?string $icon = null,
    string $group = 'general',
    bool $dangerous = false,
): void;

public function has(string $key): bool;
public function resolve(string $key): object; // FlowOperation OR FlowOperationActivity during migration
public function labelFor(string $key): string;
public function iconFor(string $key): ?string;
public function groupFor(string $key): string;
public function isDangerous(string $key): bool;
public function configFor(string $key): ?string;
public function all(): array;
```

- [ ] **Step 1: Write failing test** — covers each accessor plus duplicate-throws.

```php
it('throws DuplicateFlowOperationException on duplicate key', function () {
    $r = new OperationRegistry();
    $r->register('foo', 'Foo', FakeSlackOperation::class);
    $r->register('foo', 'Foo 2', FakeSlackOperation::class);
})->throws(DuplicateFlowOperationException::class, "Flow operation 'foo' is already registered");
```

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement** — add new fields to the `entries` array, throw on duplicate, retain `resolve()` returning a container-resolved object. Update the service provider's `bootDefaultFlowOperations()` (rename `packageRegistered()` block into a dedicated method) to use the new signature; built-ins go in with `dangerous: false` except `delete_record`, `http_request`, `update_record` (mark `dangerous: true`).

- [ ] **Step 4: Run full suite** — anything that used the registry must keep passing.

- [ ] **Step 5: Pint + commit**

```
feat(flows): expand OperationRegistry with icon/group/dangerous + duplicate guard (P9 task 5)
```

---

## Task 6: Promote `TriggerRegistry` similarly

**Files:**
- Modify: `src/Flows/Triggers/TriggerRegistry.php`
- Create: `src/Flows/Exceptions/DuplicateFlowTriggerException.php`
- Modify: `src/FilamentStudioServiceProvider.php` (`bootDefaultFlowTriggers`)
- Test: `tests/Unit/Flows/Triggers/TriggerRegistryTest.php`

New signature:

```php
public function register(
    string $key,
    string $label,
    string $handler,
    ?string $configSchema = null,
    ?string $icon = null,
): void;
```

- [ ] **Step 1: Write failing test** including duplicate-throws.

- [ ] **Step 2: Run failing test**

- [ ] **Step 3: Implement** mirror of Task 5.

- [ ] **Step 4 + Step 5: full suite, pint, commit**

```
feat(flows): expand TriggerRegistry with icon + duplicate guard (P9 task 6)
```

---

## Task 7: Refactor built-in operations to the public contracts (10 sub-tasks)

Each sub-task: write OperationContext-based test → run failing → port the activity to implement `Contracts\Flows\FlowOperation` (rename method body to accept `OperationContext` and return `OperationResult`) → add a paired `*Config` class implementing `FlowOperationConfig` → wire both into the service provider → run full suite → pint → commit.

The OperationRegistry from Task 5 keeps the legacy `FlowOperationActivity` adapter so that `FlowWorkflow` continues to function during the migration: if `resolve()` returns a legacy `FlowOperationActivity`, the workflow bridges it via a temporary `LegacyOperationAdapter` (create in Task 7.1) that wraps the old `execute(array, FlowContext)` signature into the new `FlowOperation` interface. Each sub-task converts one operation. After Task 7.10, drop `LegacyOperationAdapter` and the old `FlowOperationActivity` interface (Task 7.11).

Each operation gets its own commit. Sub-task naming below uses Task 7.N.

### Task 7.1: Bridge + `condition`

**Files:**
- Create: `src/Flows/Operations/Adapters/LegacyOperationAdapter.php`
- Modify: `src/Flows/Operations/Logic/ConditionActivity.php` → implement `FlowOperation`
- Create: `src/Flows/Operations/Logic/ConditionConfig.php`
- Modify: `src/FilamentStudioServiceProvider.php` (pass config class)
- Modify: `src/Flows/Engine/FlowWorkflow.php` (resolve via registry, wrap legacy via adapter, consume `OperationResult`)
- Test: `tests/Feature/Flows/Operations/Logic/ConditionActivityRefactorTest.php`

- [ ] **Step 1: Failing test** — `OperationContext` with sample trigger payload, `Condition` returns `OperationResult::branch('success', ...)` when filter matches, `branch('failure', ...)` otherwise.

- [ ] **Step 2: Run failing**

- [ ] **Step 3: Implement** — `execute(OperationContext $ctx): OperationResult`. Use `$ctx->config()['filter']` and `$ctx->dataChain()->toArray()`. Build `ConditionConfig::schema()` returning a single `code` field (JSON filter group) plus a `defaults()` of `['filter' => ['logic' => 'and', 'rules' => []]]` and a `validate()` that ensures `filter.logic` is `and|or`.

- [ ] **Step 4: Full suite green** — including pre-existing Phase-2 condition tests.

- [ ] **Step 5: Pint + commit**

```
refactor(flows): port condition op to FlowOperation public contract (P9 task 7.1)
```

### Task 7.2: `transform_payload`

Same TDD rhythm. Config schema: one `code` field for the JSON template. `validate()` ensures payload is a valid JSON-decodable string OR an array. Operation returns `OperationResult::success($renderedPayload)`.

Commit: `refactor(flows): port transform_payload op (P9 task 7.2)`

### Task 7.3: `log_message`

Config: `select` `level` (`debug|info|notice|warning|error`), `textarea` `message`. Returns `success(['logged' => true])`. Commit: `refactor(flows): port log_message op (P9 task 7.3)`

### Task 7.4: `trigger_flow`

Config: `flow_select` `flow_id` (required), `code` `payload`. Returns `success(['run_id' => ...])`. Commit: `refactor(flows): port trigger_flow op (P9 task 7.4)`

### Task 7.5: `send_email`

Config: `text` `to`, `text` `subject`, `textarea` `body`, optional `keyvalue` `headers`. `validate()` requires email-ish `to` (interpolation expressions allowed — skip strict validation if `{{` is present). Use `Mail::fake()` in tests. Commit: `refactor(flows): port send_email op (P9 task 7.5)`

### Task 7.6: `http_request`

Config: `select` `method` (GET/POST/PUT/PATCH/DELETE), `text` `url`, `keyvalue` `headers`, `code` `body`. On 4xx/5xx returns `OperationResult::branch('failure', $responseSummary)`. Mark `dangerous: true` in registration. Use `Http::fake()`. Commit: `refactor(flows): port http_request op (P9 task 7.6)`

### Task 7.7: `create_record`

Config: `collection_select` `collection_id` (required), `code` `data`. `validate()` requires `collection_id` resolvable. Uses `EavQueryBuilder` (via container). Returns `success(['record_id' => ...])`. Commit: `refactor(flows): port create_record op (P9 task 7.7)`

### Task 7.8: `read_record`

Config: `collection_select` `collection_id`, `text` `record_id`. Returns `success($recordPayload)` or `branch('failure', ['reason' => 'not_found'])`. Commit: `refactor(flows): port read_record op (P9 task 7.8)`

### Task 7.9: `update_record`

Mark dangerous. Config: `collection_select`, `text` `record_id`, `code` `data`. Commit: `refactor(flows): port update_record op (P9 task 7.9)`

### Task 7.10: `delete_record`

Mark dangerous. Config: `collection_select`, `text` `record_id`. Commit: `refactor(flows): port delete_record op (P9 task 7.10)`

### Task 7.11: Remove the legacy adapter

- [ ] Delete `LegacyOperationAdapter`.
- [ ] Delete `src/Flows/Operations/FlowOperationActivity.php` (the old internal interface).
- [ ] Remove `NoOpActivity` references that still use the old contract — port `NoOpActivity` to the new contract.
- [ ] `FlowWorkflow` no longer branches on legacy type — only `FlowOperation::execute` is invoked.

- [ ] **Step 1: Failing test** — assert old interface is gone via `expect(interface_exists(\Flexpik\FilamentStudio\Flows\Operations\FlowOperationActivity::class))->toBeFalse();`

- [ ] **Step 2–5: implement, full suite, pint, commit**

```
refactor(flows): drop legacy FlowOperationActivity adapter (P9 task 7.11)
```

---

## Task 8: Refactor built-in triggers to public contract

**Files:**
- Modify: `src/Flows/Triggers/ManualTrigger.php`
- Modify: `src/Flows/Triggers/WebhookTrigger.php`
- Modify: `src/Flows/Triggers/CollectionEventTrigger.php`
- Modify: `src/Flows/Triggers/Schedule/ScheduleTrigger.php`
- Create: paired `*Config` class for each (ManualTriggerConfig, WebhookTriggerConfig, CollectionEventTriggerConfig, ScheduleTriggerConfig)
- Modify: `src/FilamentStudioServiceProvider.php` (`bootDefaultFlowTriggers` wires config classes)
- Test: `tests/Feature/Flows/Triggers/BuiltinTriggersImplementPublicContractTest.php`

Internal `Flexpik\FilamentStudio\Flows\Triggers\FlowTrigger` is renamed to `Contracts\Flows\FlowTrigger` (Task 1 already created the public). For backwards compatibility, keep the old interface file as `extends Contracts\Flows\FlowTrigger` and mark it `@deprecated since 9.x — use Contracts\Flows\FlowTrigger`. After Phase 9.1 grace period it can be removed (out of scope).

- [ ] **Step 1: Failing test**

```php
it('every built-in trigger implements the public contract', function () {
    foreach ([ManualTrigger::class, WebhookTrigger::class, CollectionEventTrigger::class, ScheduleTrigger::class] as $cls) {
        expect(is_subclass_of($cls, \Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger::class))
            ->toBeTrue();
    }
});

it('every built-in trigger has a paired Config class implementing FlowTriggerConfig', function () {
    $map = [
        ManualTrigger::class => ManualTriggerConfig::class,
        WebhookTrigger::class => WebhookTriggerConfig::class,
        CollectionEventTrigger::class => CollectionEventTriggerConfig::class,
        ScheduleTrigger::class => ScheduleTriggerConfig::class,
    ];

    foreach ($map as $handler => $config) {
        expect(is_subclass_of($config, \Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig::class))
            ->toBeTrue();
    }
});
```

- [ ] **Step 2–5: implement, full suite, pint, commit**

Config schemas:
- `ManualTriggerConfig`: a single `code` field describing the manual-input schema (Phase 2A added manual-trigger input schemas — re-use existing JSON).
- `WebhookTriggerConfig`: `text` `secret_key` (mask), `select` `expected_method`.
- `CollectionEventTriggerConfig`: `collection_select` `collection_id`, `select` `event` (`created|updated|deleted`).
- `ScheduleTriggerConfig`: `text` `cron_expression`, `text` `timezone`.

Commit: `refactor(flows): port built-in triggers to public FlowTrigger contract (P9 task 8)`

---

## Task 9: Compile-time validation at publish (Phase 7 hook integration)

**Files:**
- Modify: `src/Flows/Services/PublishFlowVersion.php`
- Create: `src/Flows/Services/FlowGraphValidator.php`
- Create: `src/Flows/Exceptions/InvalidFlowGraphException.php`
- Test: `tests/Feature/Flows/Services/FlowGraphValidatorTest.php`
- Test: `tests/Feature/Flows/Services/PublishFlowVersionValidationTest.php`

`InvalidFlowGraphException` carries an array of `['node_id' => 'reason']` errors and overrides `getMessage()` to render them as a bullet list.

`FlowGraphValidator::validate(array $graph): void` walks `nodes`:
1. Resolve `data.operationKey` (operation nodes) against `OperationRegistry`. Missing → `InvalidFlowGraphException`.
2. Resolve `data.triggerType` (trigger node) against `TriggerRegistry`.
3. For each op node, locate config schema class; instantiate; merge defaults; call `validate($config)`. Any `InvalidOperationConfigException` is collected.
4. For each op marked `dangerous: true` in the registry, query the Phase 8 dangerous-op gate (`FlowSecurityGate::canPublishDangerousOperations($user)`). If false, collect a per-node error.

- [ ] **Step 1: Failing test** — three test cases:
  - publish with unknown op key → throws `InvalidFlowGraphException` mentioning the node id
  - publish with invalid op config → throws and surfaces the validator's error message
  - publish with dangerous op + non-privileged user → throws with permission message

- [ ] **Step 2: Run failing**

- [ ] **Step 3: Implement** — `PublishFlowVersion::publish()` calls `FlowGraphValidator::validate($draft->graph)` before `$draft->publish()`. Validator is constructor-injected.

- [ ] **Step 4: Full suite** — Phase 7 tests must keep passing (validator can be a no-op for happy path).

- [ ] **Step 5: Pint + commit**

```
feat(flows): validate flow graph at publish time using registries + config schemas (P9 task 9)
```

---

## Task 10: Canvas tolerates unknown operation keys in drafts

**Files:**
- Modify: `src/Api/Flows/StudioFlowsApiController.php` (or whichever controller serves `/draft` and `/publish` — locate by `grep`)
- Test: `tests/Feature/Flows/Api/UnknownOperationKeyDraftTest.php`

- [ ] **Step 1: Failing test**

```php
it('saves a draft graph referencing an unregistered op key', function () {
    $user = authenticatedFlowEditor();
    $flow = StudioFlow::factory()->create();
    $graph = sampleGraphWithOpKey('not_yet_installed_op');

    $response = $this->patchJson("/api/studio/flows/{$flow->id}/draft", ['graph' => $graph]);

    $response->assertSuccessful();
    expect($flow->draftVersion()->fresh()->graph)->toBe($graph);
});

it('refuses to publish a draft with an unknown op key', function () {
    $user = authenticatedFlowEditor();
    $flow = StudioFlow::factory()->create();
    $flow->draftVersion()->update(['graph' => sampleGraphWithOpKey('not_yet_installed_op')]);

    $response = $this->postJson("/api/studio/flows/{$flow->id}/publish");

    $response->assertStatus(422);
    expect($response->json('errors'))->toHaveKey('graph');
    expect($response->json('errors.graph.0'))->toContain('not_yet_installed_op');
});
```

- [ ] **Step 2–5: implement, full suite, pint, commit**

The draft endpoint must skip `FlowGraphValidator` entirely. The publish endpoint catches `InvalidFlowGraphException` and returns 422 with `errors.graph` shaped like the message bullets. The canvas already renders unknown-key nodes in red (xyflow node-type fallback handles this — no front-end change in scope).

Commit: `feat(flows): publish rejects unknown ops while draft tolerates them (P9 task 10)`

---

## Task 11: Frozen field-type catalog for config schemas

**Files:**
- Create: `src/Contracts/Flows/ConfigFieldType.php` (string-backed enum: `Text`, `Textarea`, `Number`, `Boolean`, `Select`, `KeyValue`, `Code`, `FlowSelect`, `CollectionSelect`)
- Create: `src/Flows/Operations/Validation/ConfigSchemaValidator.php`
- Modify: `src/Flows/Operations/OperationRegistry.php` — call `ConfigSchemaValidator::validate($configSchemaInstance)` at register time
- Modify: `src/Flows/Triggers/TriggerRegistry.php` — same
- Test: `tests/Unit/Flows/Operations/ConfigSchemaValidatorTest.php`

- [ ] **Step 1: Failing test**

```php
it('rejects a config schema referencing an unknown field type at registration', function () {
    $bad = new class implements \Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig {
        public function schema(): array { return ['fields' => [['name' => 'x', 'type' => 'magic']]]; }
        public function defaults(): array { return []; }
        public function validate(array $config): void {}
    };

    FilamentStudio::registerFlowOperation('bad', 'Bad', 'Bad', FakeSlackOperation::class, $bad::class);
})->throws(\Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException::class, "Unknown field type 'magic'");

it('accepts schemas using all 9 catalog types', function () {
    foreach (ConfigFieldType::cases() as $type) {
        $schema = new class($type) implements FlowOperationConfig {
            public function __construct(public ConfigFieldType $t) {}
            public function schema(): array { return ['fields' => [['name' => 'x', 'type' => $this->t->value]]]; }
            public function defaults(): array { return []; }
            public function validate(array $config): void {}
        };
        expect(fn () => ConfigSchemaValidator::validate($schema))->not->toThrow(Throwable::class);
    }
});
```

- [ ] **Step 2–5: implement, full suite, pint, commit**

Commit: `feat(flows): freeze 9 config field types + validate schemas at registration (P9 task 11)`

---

## Task 12: End-to-end test with a third-party operation

**Files:**
- Create: `tests/Fixtures/Flows/SlackPackage/SlackTestServiceProvider.php`
- Create: `tests/Fixtures/Flows/SlackPackage/SendSlackOperation.php`
- Create: `tests/Fixtures/Flows/SlackPackage/SendSlackOperationConfig.php`
- Test: `tests/Feature/Flows/Extensibility/ThirdPartyOperationEndToEndTest.php`

- [ ] **Step 1: Failing test**

```php
beforeEach(function () {
    $this->app->register(SlackTestServiceProvider::class);
});

it('registers, publishes, runs a third-party operation end-to-end', function () {
    Http::fake(['hooks.slack.com/*' => Http::response(['ok' => true], 200)]);

    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow)->create([
        'graph' => sampleGraphWithSlackOp(webhookUrl: 'https://hooks.slack.com/services/T/B/X', message: 'Hello {{ $trigger.user.name }}'),
    ]);

    app(PublishFlowVersion::class)->publish($version);
    $run = app(FlowDispatcher::class)->dispatch($flow->fresh(), trigger: ['user' => ['name' => 'Ada']]);

    expect($run->status)->toBe(FlowRunStatus::Succeeded);
    expect($run->steps()->where('node_id', 'slack')->first()->output)
        ->toMatchArray(['ok' => true]);
    Http::assertSent(fn ($r) => str_contains((string) $r->body(), 'Hello Ada'));
});

it('OperationContext::interpolate resolves trigger and named-op references', function () {
    /* unit-level: build a context with multiple op outputs and assert interpolate works */
});
```

`SlackTestServiceProvider::boot()` calls `FilamentStudio::registerFlowOperation(...)`. The config schema declares `text` `webhook_url` and `textarea` `message`.

- [ ] **Step 2–5: implement (only fixtures), full suite, pint, commit**

Commit: `test(flows): end-to-end third-party operation registration + run (P9 task 12)`

---

## Task 13: Arch tests for built-in operations and triggers

**Files:**
- Test: `tests/Architecture/FlowsExtensibilityArchTest.php`

- [ ] **Step 1: Failing test**

```php
use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger;

arch('every built-in operation implements FlowOperation')
    ->expect('Flexpik\FilamentStudio\Flows\Operations')
    ->classes()
    ->toImplement(FlowOperation::class)
    ->ignoring([
        'Flexpik\FilamentStudio\Flows\Operations\OperationRegistry',
        'Flexpik\FilamentStudio\Flows\Operations\Validation\ConfigSchemaValidator',
        // include any *Config class namespaces explicitly if they live alongside ops
    ]);

arch('every built-in trigger implements FlowTrigger')
    ->expect('Flexpik\FilamentStudio\Flows\Triggers')
    ->classes()
    ->toImplement(FlowTrigger::class)
    ->ignoring([
        'Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry',
        'Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry',
        // Schedule support classes
    ]);

arch('Contracts\\Flows namespace has zero non-interface dependencies on Flows\\')
    ->expect('Flexpik\FilamentStudio\Contracts\Flows')
    ->not->toUse('Flexpik\FilamentStudio\Flows\Operations')
    ->not->toUse('Flexpik\FilamentStudio\Flows\Triggers')
    ->ignoring([
        // OperationContext uses StudioFlow / StudioFlowRun models — those are in Flows\Models, which is fine. Exclude Models only.
    ]);
```

Tune `ignoring()` lists to match actual file structure after Task 7.

- [ ] **Step 2–5: implement (only by adjusting ignoring lists), full suite, pint, commit**

Commit: `test(flows): arch tests pin extensibility contracts (P9 task 13)`

---

## Task 14: Duplicate-registration regression tests

**Files:**
- Test: `tests/Feature/Flows/Extensibility/DuplicateRegistrationTest.php`

- [ ] **Step 1: Failing test**

```php
it('throws DuplicateFlowOperationException when the same op key is registered twice via facade', function () {
    FilamentStudio::registerFlowOperation('twice', 'Twice', FakeSlackOperation::class);
    FilamentStudio::registerFlowOperation('twice', 'Again', FakeSlackOperation::class);
})->throws(DuplicateFlowOperationException::class);

it('throws DuplicateFlowTriggerException when the same trigger key is registered twice via facade', function () {
    FilamentStudio::registerFlowTrigger('twice', 'Twice', FakeStripeTrigger::class);
    FilamentStudio::registerFlowTrigger('twice', 'Again', FakeStripeTrigger::class);
})->throws(DuplicateFlowTriggerException::class);

it('built-in registrations registered by the package SP also throw on collision', function () {
    FilamentStudio::registerFlowOperation('condition', 'Hijacked', FakeSlackOperation::class);
})->throws(DuplicateFlowOperationException::class);
```

- [ ] **Step 2–5: tests should already pass given Task 5/6 implementation; if not, fix and commit**

Commit: `test(flows): cover duplicate registration paths through the facade (P9 task 14)`

---

## Task 15: Documentation — `docs/extending/flows.md`

**Files:**
- Create: `docs/extending/flows.md`

Although this is a documentation deliverable (no test), we treat it as a task because Phase 9's success depends on it. Sections to include:

1. **Quickstart** — 30-line minimal package: a service provider, an op class, a config class. Tell readers to register from `boot()`.
2. **Reference** — every public class and interface signature, copy-pasted from the actual source after Task 11 (use `php artisan studio:dump-public-api` if you have a generator, otherwise inline).
3. **Field-type catalog** — table of all 9 types with example `schema()` snippets and one screenshot per type (use existing canvas screenshots from Phase 6 if available; otherwise mark `screenshot: TODO`).
4. **Semver policy** — restate from spec: `Contracts\Flows\*` is public/semver, `Flows\*` is internal/minor-may-break, deprecation gets one major release of grace.
5. **Complete example** — a Slack-notification operation as a standalone composer package (`vendor/foo/studio-slack`). Show `composer.json`, the SP, the op, the config, a test.

- [ ] **Step 1: Draft doc**

- [ ] **Step 2: Sanity-check doc compiles** — run `php -l` on inline PHP snippets (paste each into a temp file, lint, discard).

- [ ] **Step 3: Commit**

```
docs(flows): add docs/extending/flows.md with full extensibility guide (P9 task 15)
```

---

## Task 16: Phase9SmokeTest — end-to-end with audit + run-step verification

**Files:**
- Test: `tests/Feature/Flows/Phase9SmokeTest.php`

This is the final acceptance test for the phase. It uses the `SlackPackage` fixtures from Task 12 plus the Phase 8 audit log model and Phase 7 versioning service. It must exercise:

1. Register a third-party operation via the test service provider.
2. Create a flow with a manual trigger and one Slack op node.
3. Publish the flow — passes `FlowGraphValidator`.
4. Dispatch the flow with a trigger payload.
5. Assert:
   - `StudioFlowRun` row has `status = Succeeded`.
   - `StudioFlowRunStep` row for the Slack node has the expected output JSON.
   - `Http::assertSent` confirms the outgoing HTTP request body interpolated `{{ $trigger.user.name }}`.
   - Phase 8 audit-log entry exists for the publish action AND for the run dispatch action.
   - `OperationContext` for the Slack op had `tenantId` equal to the flow's tenant.

- [ ] **Step 1: Failing test** — assemble the full scenario in one Pest `it()`.

- [ ] **Step 2: Run failing**

- [ ] **Step 3: Fix every gap until green** — likely no implementation changes required by this point; failures here indicate a hole in Tasks 1–14 to be patched (and the patch goes into the correct earlier task's file with its own commit).

- [ ] **Step 4: Full suite + Step 5: Pint + commit**

```
test(flows): Phase 9 smoke test covers register → publish → run → audit (P9 task 16)
```

---

## Self-review checklist

Before opening the PR, verify all items below. Each is a `- [ ]` for the implementer to tick off.

- [ ] `Contracts\Flows\` contains exactly: `FlowOperation`, `FlowOperationConfig`, `FlowTrigger`, `FlowTriggerConfig`, `OperationContext`, `OperationResult`, `DataChain`, `ConfigFieldType` — no more, no less.
- [ ] No class outside `Contracts\Flows\` imports from `Flows\Operations\` from inside `Contracts\Flows\` (enforced by arch test in Task 13).
- [ ] All 10 built-in operations implement `Contracts\Flows\FlowOperation` and ship a paired `FlowOperationConfig` class (arch test in Task 13).
- [ ] All 4 built-in triggers implement `Contracts\Flows\FlowTrigger` and ship a paired `FlowTriggerConfig` class.
- [ ] Legacy `Flexpik\FilamentStudio\Flows\Operations\FlowOperationActivity` interface is removed (verified by Task 7.11 test).
- [ ] `FilamentStudio` facade exists at `Flexpik\FilamentStudio\Facades\FilamentStudio` and exposes exactly `registerFlowOperation(...)` and `registerFlowTrigger(...)` for Phase 9 surface. (Other facade methods may exist for unrelated features — fine.)
- [ ] Duplicate op key registration throws `DuplicateFlowOperationException`; duplicate trigger key throws `DuplicateFlowTriggerException` — both at register time, not lazily.
- [ ] `PublishFlowVersion` calls `FlowGraphValidator::validate()` before publishing.
- [ ] Unknown op key in draft = HTTP 200 save; in publish = HTTP 422 with `errors.graph`.
- [ ] `OperationContext::interpolate()` runs `{{ $trigger.x.y }}` and `{{ $op_key.field }}` references against `DataChain`.
- [ ] `OperationResult::branch()` directs the workflow to the correct outgoing edge (covered by Phase 2 branching tests, kept green by Task 7.1).
- [ ] Dangerous ops (`http_request`, `update_record`, `delete_record`, plus any third-party op with `dangerous: true`) are blocked at publish for users without the Phase 8 `run_dangerous_operations` permission.
- [ ] `ConfigSchemaValidator` rejects a schema using a field type outside the frozen 9.
- [ ] `docs/extending/flows.md` exists, lints clean, and shows a complete third-party package example.
- [ ] Phase 9 smoke test (`Phase9SmokeTest.php`) green.
- [ ] Full Pest suite green: `vendor/bin/pest --compact`.
- [ ] Pint clean: `docker exec php83 /var/www/html/crud/vendor/bin/pint --dirty --format agent` reports no changes.
- [ ] PHPStan clean (if configured): `vendor/bin/phpstan analyse`.
- [ ] No commit contains AI-attribution; all commits authored as `Serhii Fedorenko <drserhii@gmail.com>`.
- [ ] Branch is `feat/flows-p9-extensibility`, cut from `release/flows-2.0`; only Phase 9 changes are staged.

When all boxes are ticked, hand off to **superpowers:finishing-a-development-branch** to choose between PR-into-`release/flows-2.0` vs direct merge.
