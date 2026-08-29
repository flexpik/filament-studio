# Design Flow UX Improvements — Design

**Date:** 2026-06-14
**Scope:** Flow designer canvas (React/xyflow) in `resources/js/flows/`
**Backend changes:** None. Icons are mapped on the frontend; the meta API is unchanged.

## Goal

Make the Flow designer canvas easier to use along four axes:

1. A friendlier node-adding menu with icons and drag-and-drop.
2. Automatic focus on a node after it is added.
3. Deleting links and nodes by clicking a cross.
4. A user-friendly object builder for JSON-typed config fields (Payload, Data, Filter rule tree, HTTP Body) instead of raw JSON typing.

All work is confined to the package's React canvas under `resources/js/flows/`, followed by a single bundle rebuild (`npm run build` from the host app).

## Decisions (from brainstorming)

- **Object builder:** one generic, recursive object/array builder used for every `json`-typed field. Schema-agnostic; no per-field-type special casing (the Filter rule tree uses the same generic builder).
- **Escape hatch:** each JSON field keeps a `[Builder | {} JSON]` toggle. The JSON view is the existing raw textarea, preserving any shape the builder cannot express.
- **Delete + focus:** a hover/selection `×` on both nodes and edges; the Delete/Backspace key continues to work. No confirmation dialog (the draft autosaves). After any add, the new node is selected (opening its inspector) and the viewport centers on it.
- **Icons:** inline SVG (Heroicons-style paths), no new npm dependency, mapped on the frontend by operation/trigger key with a per-category fallback.

## Feature 1 — Palette: icons + drag-and-drop

**New `palette/nodeIcons.tsx`**
- Exports `iconFor(type: 'trigger' | 'operation', key: string): ReactNode`.
- Maps known keys to inline SVGs: e.g. `send_email`→envelope, `http_request`→globe, `log_message`→document, `condition`→branch, `transform_payload`→arrows, `create_record`/`read_record`/`update_record`/`delete_record`→record glyphs, `trigger_flow`→bolt; triggers `manual`→cursor, `webhook`→bolt, `collection_event`→collection, `schedule`→clock.
- Falls back to a generic per-category glyph when a key is unknown.

**`palette/NodePalette.tsx`**
- Each `PaletteItem` renders `iconFor(...)` instead of the colored dot, keeping the accent color (amber = trigger, sky = operation).
- Items become `draggable`; `onDragStart` sets `dataTransfer` with mime `application/x-flow-node` carrying `JSON.stringify({ type, key, label })` and `effectAllowed = 'move'`.
- Existing click-to-add behavior is retained.

**`flow-canvas.tsx`**
- Add `onDragOver` (calls `preventDefault`, sets `dropEffect = 'move'`) and `onDrop` on the canvas wrapper.
- On drop: read and parse the `application/x-flow-node` payload, compute the canvas position with `useReactFlow().screenToFlowPosition({ x: e.clientX, y: e.clientY })`, then add the node there.
- Extract a `createNode(type, key, label, position)` helper that builds the node object; both click-add (center of viewport) and drop (cursor position) call it.

## Feature 2 — Focus new node

- After any add (click or drop): call `selectNode(id)` (opens the inspector) and `useReactFlow().setCenter(x, y, { zoom, duration: 400 })`, where `(x, y)` is the new node's center (position + approximate half-size). This guarantees the node is visible regardless of where it landed.

## Feature 3 — Delete nodes & edges via cross

**Nodes (`nodes/OperationNode.tsx`, `nodes/TriggerNode.tsx`)**
- Add a small `×` button, positioned top-right, shown on hover or when the node is `selected`.
- `onClick` (with `stopPropagation`) calls `useFlowStore.getState().removeNode(id)` using the `id` from `NodeProps`. `removeNode` already removes connected edges and marks the graph dirty.

**Edges (`edges/BranchEdge.tsx`)**
- Render a `×` button at the edge midpoint via `EdgeLabelRenderer` (positioned with a transform on the midpoint of the straight path).
- `onClick` calls a new store action `removeEdge(id)`.

**Store (`state/useFlowStore.ts`)**
- Add `removeEdge: (id: string) => void` → `set((s) => ({ edges: s.edges.filter((e) => e.id !== id), dirty: true }))`.

**Keyboard**
- Delete/Backspace continues to work via the existing `onNodesChange`/`onEdgesChange` → `applyNodeChanges`/`applyEdgeChanges` wiring; xyflow removes connected edges on node deletion automatically.

## Feature 4 — Generic object builder for JSON fields

**New `inspector/ObjectBuilder.tsx`**
- Recursive editor for any JSON value.
  - **Object:** rows of `key | value-editor | type▾ | ×`, plus an "add field" button.
  - **Array:** indexed rows of `value-editor | type▾ | ×`, plus an "add item" button.
  - **Type picker** per row: `text | number | boolean | object | array | null`. Switching type coerces/resets the row's value (e.g. → `''`, `0`, `false`, `{}`, `[]`, `null`).
  - **Value editor** by type: text→`<input>`, number→numeric `<input>`, boolean→checkbox, object/array→nested collapsible `ObjectBuilder`, null→no editor.
- **State model:** the component keeps an internal ordered list of entries (so key edits, empty keys, and in-progress duplicate keys behave like the existing `KeyValueEditor`), and emits the reconstructed object/array via `onChange` on every change. Internal state re-syncs from props when the edited node changes (the inspector keys fields by node, so selecting a different node resets the builder).

**`inspector/FieldRenderer.tsx`**
- For the `json` field type, render the `ObjectBuilder` by default with a `[Builder | {} JSON]` toggle (local component state for the active mode).
- The JSON view keeps the current textarea with parse-on-edit behavior as the escape hatch.
- `key_value` (HTTP Headers) is unchanged — `KeyValueEditor` is already friendly.
- Because Payload, Data, Filter (rule tree), and HTTP Body are all `json`-typed in `configSchemas.ts`, they are upgraded automatically with no per-field changes.

## Testing (vitest + @testing-library/react)

- **`ObjectBuilder`**: add field, delete field, edit key, switch type (each direction), nested object, nested array, and assert the emitted JSON; array add/remove items.
- **`FieldRenderer`**: Builder↔JSON toggle preserves value; builder edits produce a correct object; raw JSON view still parses valid input.
- **`NodePalette`**: items render an icon; `onDragStart` populates `dataTransfer` with the expected payload; click-to-add still fires `onAdd`.
- **`flow-canvas`**: drop adds a node at the `screenToFlowPosition`-mapped position; after add the node is selected and `setCenter` is called (both mocked); node `×` removes the node; edge `×` removes the edge.

## Files

**New**
- `resources/js/flows/palette/nodeIcons.tsx`
- `resources/js/flows/inspector/ObjectBuilder.tsx`
- Test files under `resources/js/flows/__tests__/` for the above and the modified components.

**Modified**
- `resources/js/flows/palette/NodePalette.tsx`
- `resources/js/flows/flow-canvas.tsx`
- `resources/js/flows/nodes/OperationNode.tsx`
- `resources/js/flows/nodes/TriggerNode.tsx`
- `resources/js/flows/edges/BranchEdge.tsx`
- `resources/js/flows/state/useFlowStore.ts` (add `removeEdge`)
- `resources/js/flows/inspector/FieldRenderer.tsx`

## Out of scope

- Field-aware builders (e.g. Data driven by the target collection's fields, a dedicated operator-based filter rule-tree builder). The generic builder covers all JSON fields uniformly; smarter builders can be a later iteration.
- Backend/meta API changes.
- Changes to `key_value` (Headers) editing.
