# Phase 10 — Observability & Debugging

Date: 2026-05-11
Status: Approved design — ready for planning
Branch: `feat/flows-p10-observability`
Part of: [Flows 2.0 roadmap](2026-05-11-flows-2.0-roadmap.md)
Depends on: Phase 7 (Versioning), Phase 8 (sensitive masking)

## Goal

Ship all four observability surfaces simultaneously: richer run-detail page, dashboard widgets, dry-run + step-by-step in canvas, structured log export.

---

## Surface 1 — Richer run-detail page

Extends the existing Filament run-detail page (built on `studio_flow_runs` + `studio_flow_run_steps`).

### Schema additions on `studio_flow_run_steps`

| Column | Type | Description |
|---|---|---|
| `attempt_number` | unsigned int, default 1 | For retries; 1 for the first attempt. |
| `error_class` | string, nullable | FQCN of exception on failure. |
| `error_trace` | text, nullable | Stack trace on failure (admin-visible only). |
| `branch_taken` | string, nullable | For condition/switch nodes: which branch fired. |

### UI sections

- **Step timeline**: vertical Gantt-style timeline of operations with duration bars, color-coded by status (success / failed / skipped / branched).
- **Per-step input/output viewer**: collapsible JSON tree. Sensitive values pre-masked per Phase 8.
- **Error stack panel**: visible on failed steps. Admin sees full stack; non-admin sees `error_class` + message only.
- **Retry history**: when `attempt_number > 1`, shows each attempt with its status, duration, and error.
- **Trigger payload section**: collapsible. Already-redacted per Phase 8.
- **"Ran on version vN" pill**: links to the Phase 7 version-history viewer. For test runs: "Ran on draft (inline snapshot)".
- **Replay button**: re-runs with original payload against current published version. Confirmation modal: "This will execute against the current published version (vN), not the version this run used (vM)." Disabled if no current published version.

---

## Surface 2 — Dashboard widgets

Four new Filament widgets registered with the studio panel. Opt-in (admin adds to their dashboard).

| Widget | Description |
|---|---|
| `RecentFlowRunsWidget` | Table: last 20 runs across all flows; status badge, duration, "view" action. |
| `FlowFailureRateWidget` | Stat + sparkline: failure rate over the last 24h; trend over 7 days. |
| `TopFailingFlowsWidget` | Table: flows with most failures in last 24h; clickable. |
| `FlowDurationWidget` | Stat: avg and p95 duration over last 24h. |

All widgets respect tenant scope. All queries hit indexed columns.

### Required indexes on `studio_flow_runs`

- `(status, started_at)`
- `(flow_id, status, started_at)`
- `(tenant_id, status, started_at)` if tenancy is in use.

Indexes are added in this phase's migration if not already present.

---

## Surface 3 — Dry-run + step-by-step in canvas

### Dry-run mode

Canvas Test Run panel gains a "Dry run" toggle.

- Data operations (Create / Update / Delete / Upsert Record) become no-ops returning synthetic results:
  - `Create Record` → returns `['id' => 'dry-run-<uuid>', ...config-payload]`.
  - `Update Record` → returns `{ ...input, dry_run: true }`.
  - `Delete Record` → returns `['deleted' => true, 'id' => '<config-id>']`.
- HTTP / email / notification / dispatch-job / fire-event / artisan operations skip execution and log `"[dry-run] would have called <target>"`.
- Logic, transform, condition, switch, log-message operations run normally (no side effects).
- Runs are tagged with `dry_run = true` on `studio_flow_runs` (new boolean column, default false) and excluded from dashboard widgets by default.

### Step-by-step mode

Canvas Test Run panel gains a "Step through" toggle.

- Flow runs with a synchronous worker that pauses after each operation completes.
- Canvas polls run state; when paused, highlights the just-completed node and shows its output in the inspector panel.
- "Next step" advances; "Abort" cancels.
- Implementation: a `StepThroughExecutor` wrapping the engine, using Laravel cache + a per-run resume token. Available **only** for canvas-dispatched test runs — never for real triggers.

### Schema addition on `studio_flow_runs`

| Column | Type | Description |
|---|---|---|
| `dry_run` | boolean, default false | True for dry-run executions. Excluded from default dashboard widget queries. |

---

## Surface 4 — Structured log export

### Per-run export

`GET /api/studio/flows/runs/{id}/export`

- Returns a JSON document: run metadata + every step's input, output, error, timing.
- Requires `view_flows` permission. Tenant-scoped.
- Sensitive values masked per Phase 8.

### Bulk export

`GET /api/studio/flows/{flow}/runs/export?from=<iso>&to=<iso>&format=jsonl|csv`

- Streams runs in chosen format. Paginated internally by `started_at` cursor (no full load).
- `jsonl`: one run per line, full structure including step detail. Preferred for log shippers.
- `csv`: one row per run; columns flatten to run-level summary (no per-step detail). For spreadsheets.
- Admin-only in this phase. Tenant-scoped. Default range cap: 30 days, configurable.

### Filament UI

"Export runs" header action on ListFlowRuns with date-range and format selector.

---

## Testing

| Test | What it covers |
|---|---|
| `RunDetailStepTimelineTest` | Renders timeline, durations, statuses correctly. |
| `RunDetailMaskedOutputTest` | Step outputs scrubbed per Phase 8 rules. |
| `RunDetailRetryHistoryTest` | Multiple attempts grouped under their step. |
| `RunDetailErrorStackPermissionTest` | Non-admin sees no stack; admin sees full trace. |
| `RunDetailReplayTest` | Replay creates a new run with original payload against current published version. |
| `ReplayBlockedWhenNoPublishedVersionTest` | Replay button disabled and endpoint refuses when no current published version. |
| `RecentFlowRunsWidgetTest` | Returns last 20 runs, tenant-scoped, dry runs excluded. |
| `FlowFailureRateWidgetTest` | Correct rate and sparkline; uses indexed query (verified via explain in dev DB only). |
| `TopFailingFlowsWidgetTest` | Returns flows ranked by failures in window. |
| `FlowDurationWidgetTest` | Correct avg and p95. |
| `DryRunModeTest` | Data ops are no-ops; HTTP/email skipped; logic ops run; `dry_run` flag set on run. |
| `StepThroughExecutorTest` | Pause, resume, abort; only available for canvas test runs. |
| `RunExportSingleRunTest` | JSON shape correct; permission gated; masking applied. |
| `RunExportBulkJsonlTest` | Streams JSONL; cursor pagination doesn't load full set into memory. |
| `RunExportBulkCsvTest` | Flattens correctly; respects date range cap. |
| `CanvasDryRunAndStepThroughBrowserTest` | Browser smoke: both toggles function end-to-end. |

## Out of scope

- Resume-from-failed-step replay.
- External APM integration (OpenTelemetry, Datadog).
- Alerting / notifications on failure thresholds (build a flow yourself using the audit/runs tables).
- Custom user-defined widgets (use the existing Filament widget API directly).
- Real-time push of run updates (canvas polls; WebSocket push is out of scope).
