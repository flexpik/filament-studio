# Phase 8 — Security & Permissions Hardening

Date: 2026-05-11
Status: Approved design — ready for planning
Branch: `feat/flows-p8-security`
Part of: [Flows 2.0 roadmap](2026-05-11-flows-2.0-roadmap.md)
Depends on: Phase 7 (Versioning)

## Goal

Webhooks safe by default. Dangerous operations gated. Change-audit trail on flows.

## Permission model

Role-based, leveraging existing `studio-admin`, `studio-editor`, `studio-viewer` roles and the `StudioPermission` enum.

### New permission cases

| Permission | Default grants |
|---|---|
| `view_flows` | viewer, editor, admin |
| `create_flow` | editor, admin |
| `update_flow` | editor, admin |
| `delete_flow` | admin |
| `publish_flow` | editor, admin |
| `run_flow` | editor, admin (manual triggers) |
| `run_dangerous_operations` | admin only |

### Dangerous-operation gate

- Each operation class may declare `public const DANGEROUS = true;`.
- Initial dangerous set: HTTP request (to non-allow-listed hosts), Dispatch Job, Fire Event, Call Artisan.
- Email, Notification, and all Studio CRUD ops are not dangerous.
- At publish time: the compiler walks the draft graph; if any node has `DANGEROUS = true`, the publisher must hold `run_dangerous_operations` or publish fails with `cannot_publish_dangerous_flow`.

## Webhook auth (default HMAC)

### `studio_flows` schema additions

| Column | Type | Description |
|---|---|---|
| `webhook_auth_mode` | enum (`hmac`, `api_key`, `none`), default `hmac` | Verification mode for this flow's webhook trigger. |
| `webhook_allowed_studio_api_key_ids` | json array, nullable | For `api_key` mode: restricts which studio API keys may invoke this flow. Null = any valid studio API key. |

`webhook_secret` (already exists) is repurposed as the HMAC signing key when `auth_mode = hmac`. Auto-generated on first publish if null. Encrypted at rest.

### Verification rules

- **`hmac`**: incoming request must include `X-Studio-Signature` (HMAC-SHA256 of raw body, hex-encoded) and `X-Studio-Timestamp` (unix seconds). Compare in constant time. Reject if timestamp is more than 5 minutes from server clock (replay protection). Window is configurable via `config('filament-studio.flows.webhook_timestamp_window_seconds')`, default 300.
- **`api_key`**: request must include `X-Api-Key` header validated by existing `ValidateFlowApiKey` middleware; flow-level allow-list applied after.
- **`none`**: public. Filament UI shows red "Public endpoint" warning. Enabling requires admin role and a confirmation modal.

### Rate limiting

- Laravel rate limiter, per-flow-per-source-IP.
- Default: `60/minute`, configurable via `config('filament-studio.flows.webhook_rate_limit_per_minute')`.
- Same limit applied to manual-trigger HTTP endpoint.
- Exceeded → 429 with `Retry-After` header.

## Change-audit log

### New table: `studio_flow_audit_log`

| Column | Type |
|---|---|
| `id` | uuid PK |
| `flow_id` | uuid FK |
| `actor_id` | string, nullable (user uuid for human actors) |
| `actor_type` | enum: `user`, `system` (audit log is for *changes* to flows, not executions; webhooks/schedules don't change flow definitions) |
| `event` | string: `created`, `updated`, `published`, `rolled_back`, `deleted`, `webhook_secret_rotated`, `auth_mode_changed` |
| `metadata` | json (event-specific details: old/new values, version numbers, etc.) |
| `ip_address` | string, nullable |
| `created_at` | timestamp |

Indexed on `(flow_id, created_at desc)`.

### Wiring

- A `StudioFlowObserver` writes rows on the standard Eloquent lifecycle events.
- Publish and rollback paths (Phase 7 services) call the audit logger explicitly so they record the version number in metadata.
- Webhook-secret rotation and auth-mode change actions write rows explicitly.

### Filament UI

Read-only relation tab "Audit log" on EditFlow with infinite-scroll listing.

This is distinct from `studio_flow_runs` (execution log) — different table, different concern.

## Sensitive value masking in logs

- New utility `MasksSensitiveValues` applied to run-step input/output serialization.
- Default key regex (case-insensitive): `password`, `token`, `secret`, `api[_-]?key`, `authorization`, `bearer`. Masked as `"***"`.
- Configurable via `config('filament-studio.flows.sensitive_key_patterns')`.
- Per-flow webhook redact list (JSON pointer paths) applied to trigger payload before storage on the run.
- Masking happens at storage time (not display time) so DB never holds plaintext sensitive values.

## Webhook secret rotation

- "Rotate webhook secret" action on EditFlow header, admin only.
- Generates new secret, audit-logs event with previous secret's first 4 chars (for traceability without leak), surfaces new secret once in a modal (existing studio-api-key UX pattern).

## Testing

| Test | What it covers |
|---|---|
| `WebhookHmacVerificationTest` | Valid signature accepted, invalid rejected, missing rejected. |
| `WebhookHmacReplayProtectionTest` | Timestamp outside window rejected. |
| `WebhookApiKeyAllowlistTest` | API key not in flow's allow-list rejected even if globally valid. |
| `WebhookPublicModeWarningTest` | UI requires admin confirmation to enable `none`. |
| `WebhookRateLimitTest` | 61st request in a minute returns 429 with `Retry-After`. |
| `DangerousOpGateTest` | Editor cannot publish a flow containing an HTTP-to-unknown-host op. |
| `AuditLogObserverTest` | Each lifecycle event writes one correctly-shaped row. |
| `AuditLogExplicitEventsTest` | Publish, rollback, secret rotation, auth-mode change each write rows. |
| `SensitiveValueMaskingTest` | Run step outputs scrubbed in storage and in API responses. |
| `WebhookRedactFieldsTest` | Per-flow redact list strips fields from stored payload. |
| `RotateWebhookSecretTest` | Admin can rotate; non-admin cannot; new secret works, old fails. |

## Out of scope

- Per-webhook IP allowlist UI (config-level only).
- Per-flow user ACLs (decision: role-based).
- External secret manager integration (uses Laravel `encrypted` cast).
- Field-level encryption of run payloads (masking only).
- Anomaly detection / brute-force lockout.
