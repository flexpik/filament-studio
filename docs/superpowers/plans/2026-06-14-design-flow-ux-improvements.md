# Design Flow UX Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Improve the Flow designer canvas with palette icons + drag-and-drop, focus-on-add, hover-cross deletion of nodes/edges, and a generic recursive object builder for JSON config fields.

**Architecture:** All changes are in the package's React canvas (`packages/flexpik/filament-studio/resources/js/flows/`). The xyflow `useReactFlow()` hook supplies `screenToFlowPosition`, `setCenter`, and `getViewport`. State lives in a zustand store (`state/useFlowStore.ts`). No backend changes. A single `npm run build` from the host app at the end regenerates the bundle.

**Tech Stack:** React 18, `@xyflow/react` (v12), zustand, TypeScript, Tailwind v4. Tests: vitest + `@testing-library/react` (run from the host app root `/var/www/html/crud`).

**Working directory for all `npx vitest` commands:** `/var/www/html/crud` (the host app owns `vitest.config.ts`).

**Commit identity:** all commits must use `Serhii Fedorenko <drserhii@gmail.com>`, no AI attribution. Commit from the package repo (`packages/flexpik/filament-studio`). Use:
`git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "..."`

---

## File Structure

**New files**
- `resources/js/flows/palette/nodeIcons.tsx` — `iconFor(type, key)` → inline SVG; key→icon map with category fallback.
- `resources/js/flows/inspector/ObjectBuilder.tsx` — recursive generic JSON object/array builder.
- `resources/js/flows/__tests__/ObjectBuilder.test.tsx`
- `resources/js/flows/__tests__/nodeIcons.test.tsx`

**Modified files**
- `resources/js/flows/state/useFlowStore.ts` — add `removeEdge`.
- `resources/js/flows/inspector/FieldRenderer.tsx` — `json` type → `ObjectBuilder` + `[Builder | {} JSON]` toggle.
- `resources/js/flows/palette/NodePalette.tsx` — icons + draggable items.
- `resources/js/flows/flow-canvas.tsx` — `createNode` helper, drop handler, focus-on-add.
- `resources/js/flows/nodes/OperationNode.tsx` — hover `×` delete.
- `resources/js/flows/nodes/TriggerNode.tsx` — hover `×` delete.
- `resources/js/flows/edges/BranchEdge.tsx` — midpoint `×` delete.

**Modified tests**
- `resources/js/flows/__tests__/useFlowStore.test.ts`
- `resources/js/flows/__tests__/FieldRenderer.test.tsx`
- `resources/js/flows/__tests__/nodes.test.tsx`
- `resources/js/flows/__tests__/flow-canvas.integration.test.tsx`

---

## Task 1: Store — `removeEdge` action

**Files:**
- Modify: `resources/js/flows/state/useFlowStore.ts`
- Test: `resources/js/flows/__tests__/useFlowStore.test.ts`

- [ ] **Step 1: Write the failing test**

Append to `resources/js/flows/__tests__/useFlowStore.test.ts`:

```ts
import { createFlowStore } from '../state/useFlowStore';

describe('removeEdge', () => {
    it('removes the edge with the given id and marks dirty', () => {
        const store = createFlowStore();
        store.getState().loadFlow({
            draft_graph: {
                nodes: [],
                edges: [
                    { id: 'e1', source: 'a', target: 'b' } as any,
                    { id: 'e2', source: 'b', target: 'c' } as any,
                ],
            },
            draft_updated_at: null,
            published_version: null,
        });
        store.getState().removeEdge('e1');
        const { edges, dirty } = store.getState();
        expect(edges.map((e) => e.id)).toEqual(['e2']);
        expect(dirty).toBe(true);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/useFlowStore.test.ts`
Expected: FAIL — `removeEdge is not a function`.

- [ ] **Step 3: Add the action and its type**

In `resources/js/flows/state/useFlowStore.ts`, add to the `FlowState` type (next to `removeNode`):

```ts
    removeEdge: (id: string) => void;
```

And add the implementation inside the `create<FlowState>` object (next to `removeNode`):

```ts
        removeEdge: (id) => set((s) => ({
            edges: s.edges.filter((e) => e.id !== id),
            dirty: true,
        })),
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/useFlowStore.test.ts`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/state/useFlowStore.ts resources/js/flows/__tests__/useFlowStore.test.ts
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): add removeEdge action to canvas store"
```

---

## Task 2: Generic `ObjectBuilder` component

**Files:**
- Create: `resources/js/flows/inspector/ObjectBuilder.tsx`
- Test: `resources/js/flows/__tests__/ObjectBuilder.test.tsx`

- [ ] **Step 1: Write the failing tests**

Create `resources/js/flows/__tests__/ObjectBuilder.test.tsx`:

```tsx
import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { ObjectBuilder } from '../inspector/ObjectBuilder';

describe('ObjectBuilder', () => {
    it('renders existing object entries', () => {
        const { getByDisplayValue } = render(<ObjectBuilder value={{ title: 'Hello', count: 42 }} onChange={() => {}} />);
        expect(getByDisplayValue('title')).toBeInTheDocument();
        expect(getByDisplayValue('Hello')).toBeInTheDocument();
        expect(getByDisplayValue('42')).toBeInTheDocument();
    });

    it('adds a new field', () => {
        const onChange = vi.fn();
        const { getByText, getAllByPlaceholderText } = render(<ObjectBuilder value={{}} onChange={onChange} />);
        fireEvent.click(getByText('+ add field'));
        const keyInputs = getAllByPlaceholderText('key');
        fireEvent.change(keyInputs[keyInputs.length - 1], { target: { value: 'name' } });
        expect(onChange).toHaveBeenLastCalledWith({ name: '' });
    });

    it('edits a string value', () => {
        const onChange = vi.fn();
        const { getByDisplayValue } = render(<ObjectBuilder value={{ title: 'a' }} onChange={onChange} />);
        fireEvent.change(getByDisplayValue('a'), { target: { value: 'b' } });
        expect(onChange).toHaveBeenLastCalledWith({ title: 'b' });
    });

    it('switches a value type to number and coerces', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(<ObjectBuilder value={{ n: 'x' }} onChange={onChange} />);
        fireEvent.change(getByLabelText('type for n'), { target: { value: 'number' } });
        expect(onChange).toHaveBeenLastCalledWith({ n: 0 });
    });

    it('switches a value type to boolean', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(<ObjectBuilder value={{ flag: '' }} onChange={onChange} />);
        fireEvent.change(getByLabelText('type for flag'), { target: { value: 'boolean' } });
        expect(onChange).toHaveBeenLastCalledWith({ flag: false });
    });

    it('switches to nested object and edits a nested field', () => {
        const onChange = vi.fn();
        const { getByLabelText, getByText, getAllByPlaceholderText } = render(<ObjectBuilder value={{ meta: '' }} onChange={onChange} />);
        fireEvent.change(getByLabelText('type for meta'), { target: { value: 'object' } });
        expect(onChange).toHaveBeenLastCalledWith({ meta: {} });
        // nested "+ add field" button appears
        fireEvent.click(getByText('+ add field'));
        const keyInputs = getAllByPlaceholderText('key');
        fireEvent.change(keyInputs[keyInputs.length - 1], { target: { value: 'k' } });
        expect(onChange).toHaveBeenLastCalledWith({ meta: { k: '' } });
    });

    it('deletes a field', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(<ObjectBuilder value={{ a: '1', b: '2' }} onChange={onChange} />);
        fireEvent.click(getByLabelText('remove a'));
        expect(onChange).toHaveBeenLastCalledWith({ b: '2' });
    });

    it('edits array items', () => {
        const onChange = vi.fn();
        const { getByText, getAllByPlaceholderText } = render(<ObjectBuilder value={[]} onChange={onChange} />);
        fireEvent.click(getByText('+ add item'));
        const valInputs = getAllByPlaceholderText('value');
        fireEvent.change(valInputs[valInputs.length - 1], { target: { value: 'x' } });
        expect(onChange).toHaveBeenLastCalledWith(['x']);
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/ObjectBuilder.test.tsx`
Expected: FAIL — cannot resolve `../inspector/ObjectBuilder`.

- [ ] **Step 3: Implement the component**

Create `resources/js/flows/inspector/ObjectBuilder.tsx`:

```tsx
import React, { useEffect, useState } from 'react';

type ValueType = 'text' | 'number' | 'boolean' | 'object' | 'array' | 'null';

function typeOf(v: any): ValueType {
    if (v === null) return 'null';
    if (Array.isArray(v)) return 'array';
    if (typeof v === 'object') return 'object';
    if (typeof v === 'number') return 'number';
    if (typeof v === 'boolean') return 'boolean';
    return 'text';
}

function defaultFor(t: ValueType): any {
    switch (t) {
        case 'text': return '';
        case 'number': return 0;
        case 'boolean': return false;
        case 'object': return {};
        case 'array': return [];
        case 'null': return null;
    }
}

const TYPE_OPTIONS: ValueType[] = ['text', 'number', 'boolean', 'object', 'array', 'null'];

const inputCls = 'border rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100';

export function ObjectBuilder({ value, onChange }: { value: any; onChange: (v: any) => void }) {
    if (Array.isArray(value)) {
        return <ArrayEditor value={value} onChange={onChange} />;
    }
    return <ObjectEditor value={value && typeof value === 'object' ? value : {}} onChange={onChange} />;
}

function TypeSelect({ name, type, onChange }: { name: string; type: ValueType; onChange: (t: ValueType) => void }) {
    return (
        <select
            aria-label={`type for ${name}`}
            className={inputCls}
            value={type}
            onChange={(e) => onChange(e.target.value as ValueType)}
        >
            {TYPE_OPTIONS.map((t) => <option key={t} value={t}>{t}</option>)}
        </select>
    );
}

function ValueEditor({ value, onChange }: { value: any; onChange: (v: any) => void }) {
    const t = typeOf(value);
    if (t === 'object' || t === 'array') {
        return (
            <div className="pl-3 border-l border-gray-200 dark:border-gray-700 flex-1">
                <ObjectBuilder value={value} onChange={onChange} />
            </div>
        );
    }
    if (t === 'boolean') {
        return <input type="checkbox" checked={!!value} onChange={(e) => onChange(e.target.checked)} />;
    }
    if (t === 'number') {
        return <input type="number" placeholder="value" className={`${inputCls} flex-1`} value={value} onChange={(e) => onChange(e.target.value === '' ? 0 : Number(e.target.value))} />;
    }
    if (t === 'null') {
        return <span className="text-xs text-gray-400 flex-1">null</span>;
    }
    return <input placeholder="value" className={`${inputCls} flex-1`} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
}

function ObjectEditor({ value, onChange }: { value: Record<string, any>; onChange: (v: any) => void }) {
    const [entries, setEntries] = useState<[string, any][]>(() => Object.entries(value));

    const ownSig = JSON.stringify(Object.fromEntries(entries.filter(([k]) => k !== '')));
    const extSig = JSON.stringify(value ?? {});
    useEffect(() => {
        if (extSig !== ownSig) setEntries(Object.entries(value ?? {}));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [extSig]);

    const emit = (next: [string, any][]) => {
        setEntries(next);
        onChange(Object.fromEntries(next.filter(([k]) => k !== '')));
    };

    return (
        <div className="flex flex-col gap-1">
            {entries.map(([k, v], i) => (
                <div key={i} className="flex items-start gap-1">
                    <input
                        placeholder="key"
                        className={`${inputCls} w-28`}
                        value={k}
                        onChange={(e) => { const next = [...entries]; next[i] = [e.target.value, v]; emit(next); }}
                    />
                    <ValueEditor value={v} onChange={(nv) => { const next = [...entries]; next[i] = [k, nv]; emit(next); }} />
                    <TypeSelect name={k || `#${i}`} type={typeOf(v)} onChange={(t) => { const next = [...entries]; next[i] = [k, defaultFor(t)]; emit(next); }} />
                    <button aria-label={`remove ${k || `#${i}`}`} className="px-1 text-gray-400 hover:text-red-500" onClick={() => emit(entries.filter((_, j) => j !== i))}>×</button>
                </div>
            ))}
            <button className="text-xs text-sky-600 text-left mt-1" onClick={() => emit([...entries, ['', '']])}>+ add field</button>
        </div>
    );
}

function ArrayEditor({ value, onChange }: { value: any[]; onChange: (v: any) => void }) {
    const items = value;
    const emit = (next: any[]) => onChange(next);
    return (
        <div className="flex flex-col gap-1">
            {items.map((v, i) => (
                <div key={i} className="flex items-start gap-1">
                    <span className="text-xs text-gray-400 w-6 pt-1">{i}</span>
                    <ValueEditor value={v} onChange={(nv) => { const next = [...items]; next[i] = nv; emit(next); }} />
                    <TypeSelect name={`#${i}`} type={typeOf(v)} onChange={(t) => { const next = [...items]; next[i] = defaultFor(t); emit(next); }} />
                    <button aria-label={`remove #${i}`} className="px-1 text-gray-400 hover:text-red-500" onClick={() => emit(items.filter((_, j) => j !== i))}>×</button>
                </div>
            ))}
            <button className="text-xs text-sky-600 text-left mt-1" onClick={() => emit([...items, ''])}>+ add item</button>
        </div>
    );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/ObjectBuilder.test.tsx`
Expected: PASS (all 8 tests).

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/inspector/ObjectBuilder.tsx resources/js/flows/__tests__/ObjectBuilder.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): add generic recursive ObjectBuilder for JSON config"
```

---

## Task 3: `FieldRenderer` — builder + JSON toggle

**Files:**
- Modify: `resources/js/flows/inspector/FieldRenderer.tsx`
- Test: `resources/js/flows/__tests__/FieldRenderer.test.tsx`

- [ ] **Step 1: Write the failing tests**

Append to `resources/js/flows/__tests__/FieldRenderer.test.tsx` (import `fireEvent` if not already imported at top — the existing file already imports from `@testing-library/react`; ensure `fireEvent` is in that import):

```tsx
describe('FieldRenderer json builder', () => {
    it('renders the object builder by default and edits produce an object', () => {
        const onChange = vi.fn();
        const { getByText, getAllByPlaceholderText } = render(
            <FieldRenderer name="data" schema={{ type: 'json', label: 'Data' }} value={{}} onChange={onChange} />
        );
        fireEvent.click(getByText('+ add field'));
        const keyInputs = getAllByPlaceholderText('key');
        fireEvent.change(keyInputs[keyInputs.length - 1], { target: { value: 'title' } });
        expect(onChange).toHaveBeenLastCalledWith({ title: '' });
    });

    it('toggles to raw JSON mode and parses input', () => {
        const onChange = vi.fn();
        const { getByText, getByLabelText } = render(
            <FieldRenderer name="data" schema={{ type: 'json', label: 'Data' }} value={{}} onChange={onChange} />
        );
        fireEvent.click(getByText('{} JSON'));
        fireEvent.change(getByLabelText('Data (raw JSON)'), { target: { value: '{"a":1}' } });
        expect(onChange).toHaveBeenLastCalledWith({ a: 1 });
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/FieldRenderer.test.tsx`
Expected: FAIL — no `+ add field` / `{} JSON` elements (current `json` case renders a plain textarea).

- [ ] **Step 3: Replace the `json` case**

In `resources/js/flows/inspector/FieldRenderer.tsx`:

1. Add the import at the top (after the existing `import React` line):

```tsx
import React, { useState } from 'react';
import { ObjectBuilder } from './ObjectBuilder';
```

(Replace the existing `import React from 'react';` line with the `useState` form above.)

2. Replace the entire existing `case 'json':` block with a delegation to a dedicated component:

```tsx
        case 'json':
            return <JsonField id={id} label={schema.label} value={value} onChange={onChange} />;
```

3. Add this component at the bottom of the file (after `KeyValueEditor`):

```tsx
function JsonField({ id, label, value, onChange }: { id: string; label: string; value: any; onChange: (v: any) => void }) {
    const [mode, setMode] = useState<'builder' | 'json'>('builder');
    return (
        <div className="flex flex-col gap-1 text-sm">
            <div className="flex items-center justify-between">
                <span>{label}</span>
                <div className="inline-flex rounded border text-xs overflow-hidden dark:border-gray-600">
                    <button type="button" className={`px-2 py-0.5 ${mode === 'builder' ? 'bg-sky-500 text-white' : ''}`} onClick={() => setMode('builder')}>Builder</button>
                    <button type="button" className={`px-2 py-0.5 ${mode === 'json' ? 'bg-sky-500 text-white' : ''}`} onClick={() => setMode('json')}>{'{} JSON'}</button>
                </div>
            </div>
            {mode === 'builder'
                ? <ObjectBuilder value={value ?? {}} onChange={onChange} />
                : <textarea id={id} aria-label={`${label} (raw JSON)`} className="border rounded px-2 py-1 font-mono" rows={6}
                    value={typeof value === 'string' ? value : JSON.stringify(value ?? {}, null, 2)}
                    onChange={(e) => { try { onChange(JSON.parse(e.target.value)); } catch { onChange(e.target.value); } }} />}
        </div>
    );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/FieldRenderer.test.tsx`
Expected: PASS (existing tests + 2 new). If a pre-existing test asserted the old `json` textarea by `aria-label="Data"` without the `(raw JSON)` suffix, update that test to switch to JSON mode first (`fireEvent.click(getByText('{} JSON'))`) then target `aria-label="<label> (raw JSON)"`.

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/inspector/FieldRenderer.tsx resources/js/flows/__tests__/FieldRenderer.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): JSON config fields use ObjectBuilder with raw-JSON toggle"
```

---

## Task 4: `nodeIcons` helper

**Files:**
- Create: `resources/js/flows/palette/nodeIcons.tsx`
- Test: `resources/js/flows/__tests__/nodeIcons.test.tsx`

- [ ] **Step 1: Write the failing tests**

Create `resources/js/flows/__tests__/nodeIcons.test.tsx`:

```tsx
import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import { iconFor } from '../palette/nodeIcons';

describe('iconFor', () => {
    it('returns an svg element for a known operation key', () => {
        const { container } = render(<>{iconFor('operation', 'send_email')}</>);
        expect(container.querySelector('svg')).toBeTruthy();
    });

    it('returns an svg element for an unknown key (fallback)', () => {
        const { container } = render(<>{iconFor('trigger', 'totally_unknown_key')}</>);
        expect(container.querySelector('svg')).toBeTruthy();
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/nodeIcons.test.tsx`
Expected: FAIL — cannot resolve `../palette/nodeIcons`.

- [ ] **Step 3: Implement the helper**

Create `resources/js/flows/palette/nodeIcons.tsx`:

```tsx
import React from 'react';

const cls = 'w-4 h-4 shrink-0';
const svg = (path: React.ReactNode) => (
    <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">{path}</svg>
);

const ICONS: Record<string, React.ReactNode> = {
    // operations
    send_email: svg(<><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></>),
    http_request: svg(<><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" /></>),
    log_message: svg(<><rect x="5" y="3" width="14" height="18" rx="2" /><path d="M9 8h6M9 12h6M9 16h4" /></>),
    condition: svg(<><circle cx="6" cy="6" r="2" /><circle cx="6" cy="18" r="2" /><circle cx="18" cy="12" r="2" /><path d="M8 6h4a4 4 0 0 1 4 4M8 18h4a4 4 0 0 0 4-4" /></>),
    transform_payload: svg(<><path d="M4 7h11l-3-3M20 17H9l3 3" /></>),
    create_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M12 8v8M8 12h8" /></>),
    read_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M8 9h8M8 13h5" /></>),
    update_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="m8 14 3 3 5-7" /></>),
    delete_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M9 9l6 6M15 9l-6 6" /></>),
    trigger_flow: svg(<><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></>),
    // triggers
    manual: svg(<><path d="M7 11V6a2 2 0 0 1 4 0v5M11 8a2 2 0 0 1 4 0v3M15 9a2 2 0 0 1 4 0v6a4 4 0 0 1-4 4H9l-4-4" /></>),
    webhook: svg(<><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></>),
    collection_event: svg(<><rect x="3" y="4" width="18" height="4" rx="1" /><rect x="3" y="10" width="18" height="4" rx="1" /><rect x="3" y="16" width="18" height="4" rx="1" /></>),
    schedule: svg(<><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>),
};

const FALLBACK: Record<'trigger' | 'operation', React.ReactNode> = {
    trigger: svg(<><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></>),
    operation: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /></>),
};

export function iconFor(type: 'trigger' | 'operation', key: string): React.ReactNode {
    return ICONS[key] ?? FALLBACK[type];
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/nodeIcons.test.tsx`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/palette/nodeIcons.tsx resources/js/flows/__tests__/nodeIcons.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): add node icon map for the palette"
```

---

## Task 5: `NodePalette` — icons + drag-and-drop

**Files:**
- Modify: `resources/js/flows/palette/NodePalette.tsx`
- Test: `resources/js/flows/__tests__/NodePalette.test.tsx` (create)

- [ ] **Step 1: Write the failing tests**

Create `resources/js/flows/__tests__/NodePalette.test.tsx`:

```tsx
import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { NodePalette } from '../palette/NodePalette';

const meta = {
    triggers: [{ key: 'manual', label: 'Manual' }],
    operations: [{ key: 'send_email', label: 'Send Email' }],
};

describe('NodePalette', () => {
    it('renders an icon for each item', () => {
        const { container } = render(<NodePalette meta={meta as any} onAdd={() => {}} />);
        expect(container.querySelectorAll('svg').length).toBeGreaterThanOrEqual(2);
    });

    it('still adds on click', () => {
        const onAdd = vi.fn();
        const { getByText } = render(<NodePalette meta={meta as any} onAdd={onAdd} />);
        fireEvent.click(getByText('Send Email'));
        expect(onAdd).toHaveBeenCalledWith('operation', 'send_email', 'Send Email');
    });

    it('sets dataTransfer on drag start', () => {
        const { getByText } = render(<NodePalette meta={meta as any} onAdd={() => {}} />);
        const setData = vi.fn();
        const item = getByText('Send Email').closest('[draggable="true"]')!;
        fireEvent.dragStart(item, { dataTransfer: { setData, effectAllowed: '' } });
        expect(setData).toHaveBeenCalledWith(
            'application/x-flow-node',
            JSON.stringify({ type: 'operation', key: 'send_email', label: 'Send Email' }),
        );
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/NodePalette.test.tsx`
Expected: FAIL — no svg / no draggable element.

- [ ] **Step 3: Update the palette**

Replace the full contents of `resources/js/flows/palette/NodePalette.tsx`:

```tsx
import React, { useState } from 'react';
import { iconFor } from './nodeIcons';

type MetaEntry = { key: string; label: string };

export const FLOW_NODE_MIME = 'application/x-flow-node';

export function NodePalette({ meta, onAdd }: {
    meta: { triggers: MetaEntry[]; operations: MetaEntry[] };
    onAdd: (type: 'trigger' | 'operation', key: string, label: string) => void;
}) {
    const [search, setSearch] = useState('');
    const q = search.toLowerCase();

    const triggers = meta.triggers.filter(t => t.label.toLowerCase().includes(q) || t.key.includes(q));
    const ops = meta.operations.filter(o => o.label.toLowerCase().includes(q) || o.key.includes(q));

    return (
        <div className="w-56 flex flex-col border-r bg-gray-50 dark:bg-gray-900 dark:border-gray-700 overflow-y-auto">
            <div className="px-3 py-2 border-b dark:border-gray-700">
                <input
                    type="text"
                    placeholder="Search nodes…"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="w-full text-sm px-2 py-1 border rounded bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 outline-none focus:ring-1 focus:ring-sky-500"
                />
            </div>

            {triggers.length > 0 && (
                <Section title="Triggers">
                    {triggers.map(t => (
                        <PaletteItem key={t.key} type="trigger" itemKey={t.key} label={t.label} accent="amber" onClick={() => onAdd('trigger', t.key, t.label)} />
                    ))}
                </Section>
            )}

            {ops.length > 0 && (
                <Section title="Operations">
                    {ops.map(o => (
                        <PaletteItem key={o.key} type="operation" itemKey={o.key} label={o.label} accent="sky" onClick={() => onAdd('operation', o.key, o.label)} />
                    ))}
                </Section>
            )}

            {triggers.length === 0 && ops.length === 0 && (
                <p className="px-3 py-4 text-xs text-gray-400 dark:text-gray-500">No results</p>
            )}
        </div>
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="py-2">
            <p className="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{title}</p>
            {children}
        </div>
    );
}

function PaletteItem({ type, itemKey, label, accent, onClick }: {
    type: 'trigger' | 'operation'; itemKey: string; label: string; accent: 'amber' | 'sky'; onClick: () => void;
}) {
    const color = accent === 'amber' ? 'text-amber-500' : 'text-sky-500';
    return (
        <button
            draggable
            onDragStart={(e) => {
                e.dataTransfer.setData(FLOW_NODE_MIME, JSON.stringify({ type, key: itemKey, label }));
                e.dataTransfer.effectAllowed = 'move';
            }}
            onClick={onClick}
            className="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-200 transition-colors cursor-grab active:cursor-grabbing"
        >
            <span className={color}>{iconFor(type, itemKey)}</span>
            {label}
        </button>
    );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/NodePalette.test.tsx`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/palette/NodePalette.tsx resources/js/flows/__tests__/NodePalette.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): palette items show icons and are draggable"
```

---

## Task 6: `flow-canvas` — drop handler, `createNode`, focus-on-add

**Files:**
- Modify: `resources/js/flows/flow-canvas.tsx`
- Test: `resources/js/flows/__tests__/flow-canvas.integration.test.tsx`

- [ ] **Step 1: Write the failing tests**

Append to `resources/js/flows/__tests__/flow-canvas.integration.test.tsx`:

```tsx
import { createNode } from '../flow-canvas';

describe('createNode', () => {
    it('builds a node at the given position with config data', () => {
        const n = createNode('operation', 'send_email', 'Send Email', { x: 100, y: 50 });
        expect(n.type).toBe('operation');
        expect(n.position).toEqual({ x: 100, y: 50 });
        expect((n.data as any).label).toBe('Send Email');
        expect((n.data as any).operationType).toBe('send_email');
        expect((n.data as any).config).toEqual({});
        expect(n.id).toContain('send_email');
        expect(n.selected).toBe(true);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/flow-canvas.integration.test.tsx -t createNode`
Expected: FAIL — `createNode` is not exported.

- [ ] **Step 3: Refactor canvas — extract `createNode`, add drop + focus**

In `resources/js/flows/flow-canvas.tsx`:

1. Add `setCenter` and `screenToFlowPosition` to the `useReactFlow()` destructure (line ~41):

```tsx
    const { getViewport, setCenter, screenToFlowPosition } = useReactFlow();
```

2. Add `FLOW_NODE_MIME` to the palette import (line ~14):

```tsx
import { NodePalette, FLOW_NODE_MIME } from './palette/NodePalette';
```

3. Add this exported helper at module top level (after the imports, before `useFilamentColorMode`):

```tsx
export function createNode(type: 'trigger' | 'operation', key: string, label: string, position: { x: number; y: number }): Node {
    return {
        id: `${key}-${Date.now()}`,
        type,
        position,
        selected: true,
        data: { label, operationType: key, triggerType: key, config: {} },
    };
}
```

4. Add a shared focus helper and rewrite `handleAddNode`; also add `handleDrop`/`handleDragOver`. Replace the existing `handleAddNode` `useCallback` block with:

```tsx
    const addAndFocus = useCallback((type: 'trigger' | 'operation', key: string, label: string, position: { x: number; y: number }) => {
        const node = createNode(type, key, label, position);
        addNode(node);
        selectNode(node.id);
        setCenter(position.x + 90, position.y + 25, { zoom: getViewport().zoom, duration: 400 });
    }, [addNode, selectNode, setCenter, getViewport]);

    const handleAddNode = useCallback((type: 'trigger' | 'operation', key: string, label: string) => {
        const { x, y, zoom } = getViewport();
        const cx = (-x + window.innerWidth / 2) / zoom;
        const cy = (-y + window.innerHeight / 2) / zoom;
        addAndFocus(type, key, label, { x: cx + (Math.random() - 0.5) * 60, y: cy + (Math.random() - 0.5) * 60 });
    }, [getViewport, addAndFocus]);

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        const raw = e.dataTransfer.getData(FLOW_NODE_MIME);
        if (!raw) return;
        const { type, key, label } = JSON.parse(raw);
        const position = screenToFlowPosition({ x: e.clientX, y: e.clientY });
        addAndFocus(type, key, label, position);
    }, [screenToFlowPosition, addAndFocus]);
```

5. Add the drop handlers to the canvas wrapper `<div className="flex-1">` (the one wrapping `<ReactFlow>`):

```tsx
                <div className="flex-1" onDrop={handleDrop} onDragOver={handleDragOver}>
```

6. Ensure `selectNode` is still in the store destructure (it already is). Confirm `Node` type is imported (it is, line 3).

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/flow-canvas.integration.test.tsx`
Expected: PASS (existing `mounts, loads graph` test + new `createNode` test).

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/flow-canvas.tsx resources/js/flows/__tests__/flow-canvas.integration.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): drag-drop nodes onto canvas and focus new nodes"
```

---

## Task 7: Node delete cross (`OperationNode`, `TriggerNode`)

**Files:**
- Modify: `resources/js/flows/nodes/OperationNode.tsx`, `resources/js/flows/nodes/TriggerNode.tsx`
- Test: `resources/js/flows/__tests__/nodes.test.tsx`

- [ ] **Step 1: Write the failing tests**

Append to `resources/js/flows/__tests__/nodes.test.tsx`:

```tsx
import { fireEvent } from '@testing-library/react';
import { useFlowStore } from '../state/useFlowStore';

describe('node delete cross', () => {
    it('OperationNode × removes the node from the store', () => {
        useFlowStore.setState({
            nodes: [{ id: 'op_a', type: 'operation', position: { x: 0, y: 0 }, data: { label: 'Send Email' } } as any],
            edges: [], selectedNodeId: null, dirty: false,
        });
        const { getByLabelText } = wrap(
            <OperationNode id="op_a" data={{ label: 'Send Email', operationType: 'send_email' }} {...baseNodeProps} type="operation" />
        );
        fireEvent.click(getByLabelText('delete node'));
        expect(useFlowStore.getState().nodes).toHaveLength(0);
    });

    it('TriggerNode × removes the node from the store', () => {
        useFlowStore.setState({
            nodes: [{ id: 'trigger', type: 'trigger', position: { x: 0, y: 0 }, data: { triggerType: 'manual' } } as any],
            edges: [], selectedNodeId: null, dirty: false,
        });
        const { getByLabelText } = wrap(
            <TriggerNode id="trigger" data={{ triggerType: 'manual' }} {...baseNodeProps} />
        );
        fireEvent.click(getByLabelText('delete node'));
        expect(useFlowStore.getState().nodes).toHaveLength(0);
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/nodes.test.tsx`
Expected: FAIL — no `delete node` button.

- [ ] **Step 3: Add the delete button to both node components**

In `resources/js/flows/nodes/OperationNode.tsx`, add the import and a delete button. Replace the file body with:

```tsx
import React from 'react';
import { Handle, Position } from '@xyflow/react';
import type { NodeProps } from '@xyflow/react';
import { useFlowStore } from '../state/useFlowStore';

export function OperationNode({ id, data, selected }: NodeProps) {
    const label = (data as any).label ?? (data as any).operationType ?? 'Operation';
    return (
        <div className={`group relative px-3 py-2 rounded-md border bg-white dark:bg-gray-800 ${selected ? 'ring-2 ring-sky-500' : ''}`}>
            <button
                aria-label="delete node"
                onClick={(e) => { e.stopPropagation(); useFlowStore.getState().removeNode(id); }}
                className={`absolute -top-2 -right-2 w-5 h-5 rounded-full bg-gray-700 text-white text-xs leading-none flex items-center justify-center opacity-0 group-hover:opacity-100 ${selected ? 'opacity-100' : ''} transition-opacity`}
            >×</button>
            <div className="text-[10px] uppercase text-gray-500">Operation</div>
            <div className="text-sm font-medium">{label}</div>
            <Handle type="target" position={Position.Left} />
            <Handle type="source" position={Position.Right} id="success" style={{ top: 18 }} />
            <Handle type="source" position={Position.Right} id="failure" style={{ top: 38, background: '#ef4444' }} />
        </div>
    );
}
```

In `resources/js/flows/nodes/TriggerNode.tsx`, replace the file body with:

```tsx
import React from 'react';
import { Handle, Position } from '@xyflow/react';
import type { NodeProps } from '@xyflow/react';
import { useFlowStore } from '../state/useFlowStore';

export function TriggerNode({ id, data, selected }: NodeProps) {
    const triggerType = (data as any).triggerType ?? 'manual';
    return (
        <div className={`group relative px-3 py-2 rounded-md border bg-amber-50 dark:bg-amber-900 ${selected ? 'ring-2 ring-amber-500' : ''}`}>
            <button
                aria-label="delete node"
                onClick={(e) => { e.stopPropagation(); useFlowStore.getState().removeNode(id); }}
                className={`absolute -top-2 -right-2 w-5 h-5 rounded-full bg-gray-700 text-white text-xs leading-none flex items-center justify-center opacity-0 group-hover:opacity-100 ${selected ? 'opacity-100' : ''} transition-opacity`}
            >×</button>
            <div className="text-[10px] uppercase text-amber-700 dark:text-amber-300">Trigger</div>
            <div className="text-sm font-medium">{triggerType}</div>
            <Handle type="source" position={Position.Right} id="success" />
        </div>
    );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/nodes.test.tsx`
Expected: PASS (existing label tests + 2 new delete tests).

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/nodes/OperationNode.tsx resources/js/flows/nodes/TriggerNode.tsx resources/js/flows/__tests__/nodes.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): delete nodes via hover cross"
```

---

## Task 8: Edge delete cross (`BranchEdge`)

**Files:**
- Modify: `resources/js/flows/edges/BranchEdge.tsx`
- Test: `resources/js/flows/__tests__/BranchEdge.test.tsx` (create)

- [ ] **Step 1: Write the failing test**

Create `resources/js/flows/__tests__/BranchEdge.test.tsx`:

```tsx
import { describe, it, expect } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { ReactFlow, ReactFlowProvider } from '@xyflow/react';
import { BranchEdge } from '../edges/BranchEdge';
import { useFlowStore } from '../state/useFlowStore';

const edgeTypes = { branch: BranchEdge };

function renderGraph() {
    useFlowStore.setState({
        nodes: [
            { id: 'a', type: 'trigger', position: { x: 0, y: 0 }, data: { triggerType: 'manual' } } as any,
            { id: 'b', type: 'operation', position: { x: 200, y: 0 }, data: { label: 'Op' } } as any,
        ],
        edges: [{ id: 'e1', source: 'a', target: 'b', type: 'branch' } as any],
        selectedNodeId: null, dirty: false,
    });
    return render(
        <ReactFlowProvider>
            <div style={{ width: 500, height: 400 }}>
                <ReactFlow
                    nodes={useFlowStore.getState().nodes}
                    edges={useFlowStore.getState().edges}
                    edgeTypes={edgeTypes}
                />
            </div>
        </ReactFlowProvider>
    );
}

describe('BranchEdge delete', () => {
    it('removes the edge from the store when × is clicked', () => {
        const { getByLabelText } = renderGraph();
        fireEvent.click(getByLabelText('delete edge'));
        expect(useFlowStore.getState().edges).toHaveLength(0);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/BranchEdge.test.tsx`
Expected: FAIL — no `delete edge` button.

- [ ] **Step 3: Add the midpoint delete button**

Replace the full contents of `resources/js/flows/edges/BranchEdge.tsx`:

```tsx
import React from 'react';
import { BaseEdge, EdgeLabelRenderer, getStraightPath, type EdgeProps } from '@xyflow/react';
import { useFlowStore } from '../state/useFlowStore';

export function BranchEdge(props: EdgeProps) {
    const { id, sourceX, sourceY, targetX, targetY } = props;
    const [path, labelX, labelY] = getStraightPath({ sourceX, sourceY, targetX, targetY });
    const color = props.sourceHandleId === 'failure' ? '#ef4444' : '#0ea5e9';
    return (
        <>
            <BaseEdge {...props} path={path} style={{ stroke: color, strokeWidth: 2 }} />
            <EdgeLabelRenderer>
                <button
                    aria-label="delete edge"
                    className="nodrag nopan absolute w-5 h-5 rounded-full bg-gray-700 text-white text-xs leading-none flex items-center justify-center opacity-0 hover:opacity-100 focus:opacity-100 transition-opacity"
                    style={{ transform: `translate(-50%, -50%) translate(${labelX}px, ${labelY}px)`, pointerEvents: 'all' }}
                    onClick={(e) => { e.stopPropagation(); useFlowStore.getState().removeEdge(id); }}
                >×</button>
            </EdgeLabelRenderer>
        </>
    );
}
```

> Note: the `×` is only visible on hover in the live canvas, but it is always present in the DOM, so `getByLabelText('delete edge')` finds it and `fireEvent.click` works in tests.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /var/www/html/crud && npx vitest run packages/flexpik/filament-studio/resources/js/flows/__tests__/BranchEdge.test.tsx`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add resources/js/flows/edges/BranchEdge.tsx resources/js/flows/__tests__/BranchEdge.test.tsx
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "feat(flows): delete edges via midpoint cross"
```

---

## Task 9: Full suite + bundle build

**Files:** none (verification only)

- [ ] **Step 1: Run the full JS test suite**

Run: `cd /var/www/html/crud && npx vitest run`
Expected: all test files pass (including the four new files and the four modified ones). If any pre-existing test broke due to the `FieldRenderer` `json` change or node-component signature, fix it per the notes in Tasks 3 and 7, then re-run.

- [ ] **Step 2: Build the production bundle**

Run: `cd /var/www/html/crud && npm run build`
Expected: build succeeds; a new `public/build/assets/flow-canvas-*.js` hash is emitted (the old hash is removed). Note: `public/build/` is gitignored in the crud repo — do not commit it.

- [ ] **Step 3: Final commit (if any test fixups were needed)**

```bash
cd /var/www/html/crud/packages/flexpik/filament-studio
git add -A resources/js
git -c user.name="Serhii Fedorenko" -c user.email="drserhii@gmail.com" commit -m "test(flows): finalize design-flow UX test suite" || echo "nothing to commit"
```

- [ ] **Step 4: Manual smoke (optional, user-driven)**

Open `https://crud.local/admin/flows/<id>/design` after a hard refresh and verify: palette icons + drag-drop, node centers/selects on add, node/edge `×` deletes, and JSON fields show the Builder/JSON toggle with a working object builder.

---

## Self-Review Notes

- **Spec coverage:** Feature 1 (palette icons + drag) → Tasks 4, 5, 6; Feature 2 (focus on add) → Task 6 (`addAndFocus` → `setCenter` + `selectNode`); Feature 3 (delete nodes/edges via cross) → Tasks 1, 7, 8; Feature 4 (generic object builder + JSON toggle) → Tasks 2, 3. All four covered.
- **Type consistency:** `createNode(type, key, label, position)` and `addAndFocus(type, key, label, position)` share the same signature shape; `removeEdge(id)` defined in Task 1 is used in Task 8; `FLOW_NODE_MIME` defined in Task 5 is imported in Task 6; `iconFor(type, key)` defined in Task 4 is used in Task 5.
- **Escape hatch:** raw-JSON textarea retained in Task 3 (`JsonField`).
- **Out of scope (per spec):** no field-aware/rule-tree builder, no backend changes, `key_value` Headers editor untouched.
