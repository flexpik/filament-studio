# Flows (Automation Engine)

Flows is an opt-in workflow automation subsystem: triggers start a run, a directed graph of
operations executes in order, and every run is recorded for observability. Flows are designed
visually on a canvas, edited as a draft, and explicitly published before they can run for real.

## Enabling

Flows is disabled by default.

```bash
php artisan vendor:publish --tag="filament-studio-config"
```

```php
// config/filament-studio.php
'flows' => [
    'enabled' => env('STUDIO_FLOWS_ENABLED', false),
],
```

```bash
STUDIO_FLOWS_ENABLED=true
```

Enabling it registers the **Flows** resource in the admin panel, the Flows REST API, and the
`studio:flows:dispatch-scheduled` command used by Schedule triggers.

## Draft, Design, Publish

Every flow has a **draft** graph (edited live on the canvas) and, once published, an immutable
**published version**. Publishing copies the draft into a new `StudioFlowVersion` snapshot and
clears the draft; the previously published version stays in history with a **Restore** action.

1. **Create** — set name, slug, status, logging mode, and webhook auth mode.
2. **Design** — open the canvas, drag triggers/operations from the palette, connect edges, and
   configure each node in the inspector panel.
3. **Save** — persists the draft graph without publishing.
4. **Test Run** — executes the current *draft* graph inline (optionally as a dry run, or
   step-through for debugging one node at a time) without affecting published behavior.
5. **Publish** — validates the graph, gates dangerous operations and public webhooks behind an
   explicit confirmation, and creates the new published version. Only a published version can be
   triggered for real.

## Triggers

| Trigger | Fires when |
|---|---|
| Manual | Invoked directly via **Run Now** or the API |
| Webhook | An inbound HTTP request hits the flow's webhook URL |
| Collection Event | A record in a chosen collection is created, updated, or deleted |
| Schedule | A cron expression matches, checked every minute by `studio:flows:dispatch-scheduled` |

## Operations

| Category | Operations |
|---|---|
| Logic | Condition, Log Message |
| Data | Transform Payload |
| Records | Create Record, Read Record, Update Record†, Delete Record† |
| Communication | Send Email, HTTP Request† |
| Composition | Trigger Flow (calls another flow, depth-limited by `flows.max_call_depth`) |

† Marked **dangerous** — publishing a graph containing these requires an explicit confirmation
gate (`AssertCanPublishDangerousGraph`).

Operation configs are interpolated through a template engine, so values can reference the
trigger payload or a previous step's output, e.g. `{{ trigger.payload.email }}`.

## Runs & Observability

Every execution — manual, scheduled, webhook-triggered, or a test run — creates a
`StudioFlowRun` with a tree of `StudioFlowRunStep` records (one per node), each capturing input,
output, status, timing, and retry attempt. The Flows list page includes dashboard widgets for
recent runs, failure rate, duration, and top-failing flows, and each run can be inspected step by
step or replayed.

Runs execute synchronously or via the queue (`flows.queue` / `flows.connection`), and completed
run history is pruned after `flows.log_retention_days` (default 30).

## Webhook Security

Webhook-triggered flows support three auth modes, set per-flow:

- **HMAC signature** — requests are verified against a rotatable per-flow secret with a
  timestamp window (`flows.webhook_timestamp_window_seconds`) to reject replays; secrets rotate
  via **Rotate webhook secret** without downtime.
- **API key** — reuses the existing `StudioApiKey` auth.
- **Public (no auth)** — requires an explicit confirmation gate to enable
  (`AssertCanEnablePublicWebhook`), since anyone with the URL can trigger the flow.

All public webhooks are rate-limited (`flows.webhook_rate_limit_per_minute`, default 60) and can
be restricted to an IP allowlist (`flows.webhook_ip_allowlist`).

Values matching `flows.sensitive_key_patterns` (password, token, secret, api key, authorization,
bearer, by default) are redacted before step logs are persisted.

## REST API

Registered under the configured API prefix when `flows.enabled` is true, protected by the same
`X-Api-Key` auth as the rest of the Studio API:

```
GET    /api/studio/flows
POST   /api/studio/flows
GET    /api/studio/flows/{id}
PUT    /api/studio/flows/{id}
DELETE /api/studio/flows/{id}
GET    /api/studio/flows/{id}/graph
PUT    /api/studio/flows/{id}/graph
POST   /api/studio/flows/{id}/publish
POST   /api/studio/flows/{id}/test
POST   /api/studio/flows/{id}/run
GET    /api/studio/flows/{id}/runs
GET    /api/studio/flows/{id}/runs/{runId}
GET    /api/studio/flows/{id}/runs/export
GET    /api/studio/flows/{id}/versions
GET    /api/studio/flows/{id}/versions/{versionId}
POST   /api/studio/flows/{id}/versions/{versionId}/rollback
POST   /api/studio/flows/runs/{runId}/cancel
POST   /api/studio/flows/runs/{runId}/replay
POST   /api/studio/flows/runs/{runId}/step
GET    /api/studio/flows/runs/{runId}/export
GET    /api/studio/flows/meta/triggers
GET    /api/studio/flows/meta/operations
GET    /api/studio/flows/meta/collections
```

## MCP & Permissions

The `_studio.flows` MCP management scope grants AI-assistant access to flow management (see
[MCP Server](mcp.md)). In the admin panel, flow access is governed by `FlowPolicy`, synced
through the same permission system as collections (see [Authorization](authorization.md)).

## Extending

Register your own operations and triggers via the plugin API — see
[Extending Flows](extending/flows.md) for the full guide, including a complete worked example.

```php
FilamentStudioPlugin::registerFlowOperation(
    key: 'slack.send_message',
    label: 'Send Slack Message',
    activity: SendSlackMessageActivity::class,
    configSchema: SendSlackMessageConfig::class,
);
```
