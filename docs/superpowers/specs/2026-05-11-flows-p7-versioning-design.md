# Phase 7 — Versioning & Publishing

Date: 2026-05-11
Status: Approved design — ready for planning
Branch: `feat/flows-p7-versioning`
Part of: [Flows 2.0 roadmap](2026-05-11-flows-2.0-roadmap.md)

## Goal

Drafts are the canvas's working copy. Triggers only fire the published version. Every run is pinned to the exact version that executed it.

## Semantics (decision)

Strict: production runs the published version only. Drafts run only via explicit "Test Run" from the canvas. Editing always produces a new draft; publishing supersedes the prior published version.

## Schema changes

### `studio_flows`

Add:

| Column | Type | Description |
|---|---|---|
| `published_version_id` | uuid, nullable, FK → `studio_flow_versions.id` | Currently live version. Null = never published. |
| `draft_graph` | json, nullable | In-progress graph. Canvas autosaves here. |
| `draft_updated_at` | timestamp, nullable | For "draft saved Ns ago" UI. |

### `studio_flow_versions`

Already has `id`, `flow_id`, `version`, `graph`, `published_at`, `change_summary`, `created_at`. Add:

| Column | Type | Description |
|---|---|---|
| `published_by` | string, nullable | Actor identifier (user uuid or system tag) who published. |

Add index: `(flow_id, published_at desc)` for history list.

### `studio_flow_runs`

Add:

| Column | Type | Description |
|---|---|---|
| `flow_version_id` | uuid, nullable, FK → `studio_flow_versions.id` | Pinned execution version. Null only for test runs using an inline snapshot. |
| `inline_graph` | json, nullable | Graph snapshot used only when `flow_version_id` is null (canvas test runs against unpublished drafts). |

## Lifecycle

### Save (autosave)

- Writes `flows.draft_graph` and updates `draft_updated_at`.
- Does not affect runtime.

### Publish

- Validates the draft graph (compile-time validator from existing engine).
- Inside a DB transaction:
  1. Insert new `studio_flow_versions` row: `version = max(version)+1`, `graph = draft_graph`, `published_at = now()`, `change_summary` from user input, `published_by` from auth.
  2. Update `flows.published_version_id` to the new row.
  3. Clear `flows.draft_graph` (draft now matches published).

### Edit after publish

- First mutation copies `flows.published_version_id.graph` into `flows.draft_graph`.
- Autosave loop begins.

### Rollback

- "Publish version N" on the history page is a publish operation that copies an older version's graph into a new version row.
- History is append-only — restoring v3 creates v(N+1) with the same graph as v3.

### Test Run from canvas

- Dispatches a run with `flow_version_id = null` and the current draft graph stored in `runs.inline_graph`.
- Runtime reads from `inline_graph` when `flow_version_id` is null.

## Runtime contract change (breaking)

- All trigger types (webhook, schedule, collection event, manual button, manual API) refuse to fire if `flows.published_version_id` is null. Response: HTTP 409 (for webhook/manual API) or a `failed` run row with reason `no_published_version` (for schedule/collection events).
- `FlowDispatcher::dispatchAsync()` signature gains an optional `?StudioFlowVersion $version` parameter. Default resolves to `flow->publishedVersion`. Test runs pass an explicit unpublished snapshot.

## Filament UI

- **EditFlow** header pill: `Draft (unsaved changes)` / `Draft saved Ns ago` / `Published vN`.
- **Publish button** opens modal with optional change-summary textarea.
- **Version history tab** on EditFlow: list of versions (number, published_at, published_by, change_summary). Actions: "View" (read-only canvas), "Restore" (creates new version from this one's graph).
- **Run-detail page**: shows "Ran on version vN" with link to history; for test runs shows "Ran on draft (inline snapshot)".

## Migration of existing MVP data

Migration step writes one `studio_flow_versions` row per existing flow that has a graph (version 1, `graph` from current source, `published_at = now()`, `change_summary = 'Migrated from MVP'`) and sets `flows.published_version_id`. Flows without a graph remain unpublished.

## Testing

New directory: `tests/Feature/Flows/Versioning/`

| Test | What it covers |
|---|---|
| `PublishFlowTest` | Publish creates new version row, updates pointer, clears draft, in a transaction. |
| `DraftSaveTest` | Autosave writes only to `draft_graph`, doesn't affect runtime. |
| `RollbackTest` | Restoring vN creates v(N+1) with same graph; history append-only. |
| `TriggerRefusesUnpublishedTest` | All 4 trigger types fail closed when `published_version_id` is null. |
| `RunPinsVersionTest` | A run's `flow_version_id` is set on dispatch and not affected by later publishes. |
| `TestRunUsesInlineGraphTest` | Canvas test run stores `inline_graph` and uses it during execution. |
| `EditAfterPublishCopiesDraftTest` | First edit after publish hydrates `draft_graph` from published version. |
| `MigrateMvpFlowsTest` | Migration mints v1 for each existing flow with a graph. |

## Out of scope

- Branch-style drafts.
- Per-version diff viewer (history list only).
- Scheduled publish.
- Auto-rollback on failed runs.
