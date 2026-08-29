# Flow Canvas — Fullscreen + Mobile-Friendly — Design

**Date:** 2026-06-14
**Scope:** Flow designer canvas (React/xyflow) in `resources/js/flows/` + the host page blade.
**Backend changes:** None.

## Goal

Two improvements to the Flow designer:

1. A **fullscreen** button that expands the designer to fill the whole viewport (covering the Filament sidebar/topbar), with Esc/button to exit.
2. **Mobile-friendliness**: the palette and inspector become slide-in drawers on small screens (instead of fixed side columns that crush the canvas), the toolbar wraps instead of overflowing, and node/edge deletion works by tap.

All work is confined to the package's React canvas and one host blade, followed by a single `npm run build`.

## Decisions (from brainstorming)

- **Fullscreen = in-app overlay** (CSS `fixed inset-0`), not the native Fullscreen API. Reliable cross-browser, keeps browser chrome, predictable.
- **Mobile palette/inspector = slide-in drawers** with a backdrop on small screens; unchanged side-column layout on `md+`.
- Touch deletion: node crosses already show when a node is selected; the edge cross must also show when the edge is selected.

## Breakpoint

Tailwind `md` (768px) is the divider between "mobile" (drawers, wrapping toolbar) and "desktop" (side columns).

## Feature 1 — Fullscreen overlay

**`flow-canvas.tsx` (`CanvasInner`)**
- Add `const [fullscreen, setFullscreen] = useState(false);`.
- The outer wrapper currently is `<div className="flex flex-col h-full">`. When `fullscreen`, it becomes a full-viewport overlay; otherwise it keeps `h-full`. Concretely the className is computed:
  - fullscreen: `fixed inset-0 z-40 flex flex-col bg-white dark:bg-gray-900`
  - normal: `flex flex-col h-full`
- Add an `Esc` key listener (only active while `fullscreen`) that calls `setFullscreen(false)`. Implement with a `useEffect` that adds/removes a `keydown` handler keyed on `fullscreen`.
- Pass `fullscreen` and `onToggleFullscreen={() => setFullscreen(v => !v)}` to `Toolbar`.
- ReactFlow adapts to the container size change automatically (its internal ResizeObserver); no manual refit required.

**`toolbar/Toolbar.tsx`**
- Add two props: `fullscreen: boolean` and `onToggleFullscreen: () => void`.
- Add a button (in the right-hand button group, before Save) with:
  - `aria-label` = `"Enter fullscreen"` when not fullscreen, `"Exit fullscreen"` when fullscreen.
  - An expand icon when not fullscreen, a collapse icon when fullscreen (inline SVG).
  - `onClick={onToggleFullscreen}`.

## Feature 2 — Mobile drawers + responsive toolbar

**`flow-canvas.tsx` layout**
- The panels row changes from `<div className="flex flex-1 min-h-0">` to `<div className="flex flex-1 min-h-0 relative">` (so the drawers/backdrop can absolutely position within it).
- **Palette** (rendered when `paletteOpen`): wrap in a container with responsive classes:
  - small: `absolute inset-y-0 left-0 z-30 w-64 shadow-xl`
  - `md+`: `md:relative md:z-auto md:w-56 md:shadow-none`
  - The existing `NodePalette` keeps its own internal styling; the wrapper provides positioning/width. (NodePalette's hard-coded `w-56` is moved to the wrapper so the drawer can be `w-64` on mobile; NodePalette becomes `w-full h-full`.)
- **Inspector** (`InspectorPanel`, rendered when a node is selected): same pattern on the right:
  - small: `absolute inset-y-0 right-0 z-30 w-full max-w-sm shadow-xl`
  - `md+`: `md:relative md:z-auto md:max-w-none md:w-96 md:shadow-none`
  - InspectorPanel's hard-coded `w-96` moves to the wrapper; InspectorPanel becomes `w-full h-full`.
- **Backdrop**: when `paletteOpen` OR an inspector node is selected, render a `md:hidden absolute inset-0 bg-black/30 z-20` div. Clicking it closes whichever panel(s) are open (`setPaletteOpen(false)` and `selectNode(null)`). Two separate backdrops (one per panel) are acceptable; a single shared backdrop that closes both is simpler and preferred.

**`toolbar/Toolbar.tsx` responsiveness**
- The toolbar header row gets `flex-wrap gap-2` so buttons wrap to a second line on narrow screens instead of overflowing.
- The flow name span gets `truncate max-w-[40vw]`.
- The status pill is hidden on the smallest screens: add `hidden sm:inline`.

## Feature 3 — Touch-friendly edge deletion

**`edges/BranchEdge.tsx`**
- The delete button currently uses `opacity-0 hover:opacity-100 focus:opacity-100`. Add visibility when the edge is selected: include `${props.selected ? 'opacity-100' : ''}` in the className so tapping an edge (which selects it in xyflow) reveals the `×`.

## Feature 4 — Host blade height

**`resources/views/flows/pages/design-flow.blade.php`**
- Change the canvas root height from `h-[calc(100vh-12rem)]` to `h-[calc(100dvh-10rem)]` (dynamic viewport height so mobile browser chrome doesn't clip the canvas, and slightly taller).

## Testing (vitest + @testing-library/react)

- **`Toolbar`**: renders a fullscreen toggle button (`aria-label` "Enter fullscreen"); clicking it calls `onToggleFullscreen`. (Pass the two new props in existing Toolbar tests that construct `<Toolbar>` so they keep compiling — give them `fullscreen={false}` and `onToggleFullscreen={() => {}}`.)
- **`flow-canvas` integration**: with the palette open, a mobile backdrop element (`.bg-black\/30` / a labelled element) is present; clicking it closes the palette. (Assert via the store/DOM, since jsdom doesn't apply the `md:hidden` breakpoint — the element is still in the DOM.)
- **`BranchEdge`**: rendering with `selected: true` produces a delete button whose className includes `opacity-100`.
- Responsive breakpoint behavior (drawer vs column, toolbar wrap) is pure CSS and is **not** unit-tested; it is verified via `npm run build` and a manual mobile-width check.

## Files

**Modified**
- `resources/js/flows/flow-canvas.tsx` (fullscreen state + overlay; responsive drawer wrappers + backdrop)
- `resources/js/flows/toolbar/Toolbar.tsx` (fullscreen button + responsive header)
- `resources/js/flows/palette/NodePalette.tsx` (root `w-56` → `w-full h-full`; width owned by the canvas wrapper)
- `resources/js/flows/inspector/InspectorPanel.tsx` (root `w-96` → `w-full h-full`; width owned by the canvas wrapper)
- `resources/js/flows/edges/BranchEdge.tsx` (show `×` when edge selected)
- `resources/views/flows/pages/design-flow.blade.php` (dvh height)

**Modified tests**
- `resources/js/flows/__tests__/Toolbar.test.tsx`
- `resources/js/flows/__tests__/flow-canvas.integration.test.tsx`
- `resources/js/flows/__tests__/BranchEdge.test.tsx`

## Out of scope

- Native Fullscreen API.
- Reworking ReactFlow's own touch gestures (pan/zoom already work on touch).
- Backend / API changes.
- The unrelated `dry_run` column error in `studio_flow_runs` (a separate DB/migration issue, tracked separately).
