# Phase 9 — Extensibility API

Date: 2026-05-11
Status: Approved design — ready for planning
Branch: `feat/flows-p9-extensibility`
Part of: [Flows 2.0 roadmap](2026-05-11-flows-2.0-roadmap.md)
Depends on: Phase 7 (Versioning), Phase 8 (dangerous-op gate)

## Goal

Third-party packages can register flow operations and triggers via plugin-style registry calls in a service provider. PHP-side only — operation logic plus a JSON-schema config rendered by the existing canvas FieldRenderer.

## Public registration API

```php
use Flexpik\FilamentStudio\Facades\FilamentStudio;

// In a third-party package's ServiceProvider::boot():
FilamentStudio::registerFlowOperation(
    key: 'send_slack_message',
    label: 'Send Slack Message',
    icon: 'heroicon-o-chat-bubble-left',
    group: 'communication',
    activity: SendSlackMessageActivity::class,
    configSchema: SendSlackMessageConfig::class,
    dangerous: false,
);

FilamentStudio::registerFlowTrigger(
    key: 'stripe_webhook',
    label: 'Stripe Webhook',
    icon: 'heroicon-o-credit-card',
    handler: StripeWebhookTrigger::class,
    configSchema: StripeWebhookConfig::class,
);
```

`group` is one of: `logic`, `data`, `communication`, `developer`, `workflow`, or any custom string (used as the palette section header).

## Public contracts

All contracts live under `Flexpik\FilamentStudio\Contracts\Flows\` and follow semver. Changes ship in major releases.

### `FlowOperation`

```php
interface FlowOperation
{
    public function execute(OperationContext $context): OperationResult;
}
```

### `FlowOperationConfig`

```php
interface FlowOperationConfig
{
    /** @return array JSON-schema-subset describing the operation's config fields */
    public function schema(): array;

    /** @return array Default values keyed by field name */
    public function defaults(): array;

    /**
     * Validate user-supplied config.
     * @throws InvalidOperationConfigException
     */
    public function validate(array $config): void;
}
```

### `FlowTrigger`

The existing internal `FlowTrigger` interface is promoted to public namespace unchanged in shape.

### `OperationContext` (public, immutable class)

Methods:

- `dataChain(): DataChain` — read-only accessor over `$trigger`, `$last`, and named operation outputs.
- `flow(): StudioFlow`
- `run(): StudioFlowRun`
- `tenantId(): ?string`
- `interpolate(string $template): mixed` — runs `{{ $trigger.x }}` interpolation against the data chain.
- `config(): array` — the operation's stored config, post-validation, with defaults merged.

### `OperationResult` (public class)

Factory methods:

- `static success(mixed $output): self`
- `static fail(string $message, ?Throwable $previous = null): self`
- `static branch(string $branchKey, mixed $output): self` — for condition/switch ops.

## Config schema → canvas form

The existing React FieldRenderer supports 9 JSON-schema field types. Phase 9 documents this as the **frozen** public contract:

| Type | Notes |
|---|---|
| `text` | Single-line input. |
| `textarea` | Multi-line input. |
| `number` | Numeric input. |
| `boolean` | Toggle. |
| `select` | Static `options` or `$source` reference for dynamic options. |
| `keyvalue` | Map editor (headers, query params, etc.). |
| `code` | Mono-font editor (e.g., JSON body, expression). |
| `flow_select` | Picks another flow by id. |
| `collection_select` | Picks a Studio collection by id. |

Adding a 10th field type is a minor version bump. Removing or changing one is a major.

Reference values (e.g., `{{ $trigger.foo.bar }}`) are stored verbatim as strings. The operation interpolates them at execute time via `$context->interpolate()`.

## Discovery and load order

- Operations and triggers are registered in service provider `boot()`.
- The package's own service provider registers built-ins at priority 0 during `boot()`.
- Third parties register after.
- Duplicate `key` throws `DuplicateFlowOperationException` (or `DuplicateFlowTriggerException`) at register time — catches typos and version-skew bugs early.

## Compile-time validation

When a flow is published (Phase 7 hook), the compiler walks the graph and confirms:

1. Every operation/trigger `key` resolves to a registered handler.
2. Every operation's stored config passes `FlowOperationConfig::validate()`.
3. Operations marked `dangerous: true` are gated by `run_dangerous_operations` permission (Phase 8 hook).

Unknown keys in a draft (not yet published) are flagged in the canvas as red nodes but don't crash the runtime.

## Built-in operations refactor

All built-in operations are migrated to implement the new public contracts. An arch test enforces this. No behavior change — purely making the existing code use the public API surface that third parties will use.

## Documentation

New doc: `docs/extending/flows.md`. Contents:

- Quickstart: minimal third-party operation package (service provider + activity + config class).
- Reference: every public class/interface signature.
- Field-type catalog: each of the 9 field types with config-schema example and screenshot.
- Versioning policy: semver of the public API.
- Complete example: a Slack-notification operation as a standalone package.

## Versioning the public API

- `Flexpik\FilamentStudio\Contracts\Flows\*` — public, semver.
- `Flexpik\FilamentStudio\Flows\*` — internal, may change in minor releases.
- Internal classes used by third parties accidentally → covered by a CHANGELOG "deprecated" notice and a 1-major-release grace period before removal.

## Testing

| Test | What it covers |
|---|---|
| `RegisterCustomOperationTest` | Registers a fake op, publishes a flow using it, runs it end-to-end. |
| `RegisterCustomTriggerTest` | Same for a custom trigger. |
| `DuplicateOperationKeyThrowsTest` | Registering the same key twice throws at register time. |
| `UnknownOperationKeyHandlingTest` | Graph referencing removed operation: publish fails clearly; draft shows red node. |
| `ConfigSchemaValidationTest` | Invalid config rejected by `validate()` blocks publish. |
| `DangerousOperationGateTest` | Custom op with `dangerous: true` gated by permission at publish. |
| `OperationContextInterpolationTest` | `$context->interpolate()` resolves trigger and named-operation references. |
| `OperationResultBranchTest` | `OperationResult::branch()` directs flow to the correct edge. |
| `BuiltinsImplementPublicContractTest` | Arch test: every built-in operation implements `Contracts\Flows\FlowOperation`. |

## Out of scope

- Custom React canvas node components.
- Hot-reload of operations without app restart.
- Per-tenant operation overrides / opt-outs.
- Marketplace / discovery service.
- Operation versioning (per-operation semver beyond the package).
