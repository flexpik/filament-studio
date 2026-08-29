import React, { useEffect, useRef, useState } from 'react';

let rowIdSeq = 0;
const nextRowId = () => `row-${rowIdSeq++}`;

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

type ObjEntry = { id: string; k: string; v: any };

function ObjectEditor({ value, onChange }: { value: Record<string, any>; onChange: (v: any) => void }) {
    const [entries, setEntries] = useState<ObjEntry[]>(() =>
        Object.entries(value).map(([k, v]) => ({ id: nextRowId(), k, v }))
    );
    // Track the last value we emitted so we can distinguish our own updates from external prop changes.
    const lastEmitted = useRef<string>(JSON.stringify(value ?? {}));

    const extSig = JSON.stringify(value ?? {});
    useEffect(() => {
        // Only sync from props when the incoming value is truly external (not something we emitted).
        if (extSig !== lastEmitted.current) {
            lastEmitted.current = extSig;
            setEntries(Object.entries(value ?? {}).map(([k, v]) => ({ id: nextRowId(), k, v })));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [extSig]);

    const emit = (next: ObjEntry[]) => {
        const obj = Object.fromEntries(next.filter((e) => e.k !== '').map((e) => [e.k, e.v]));
        lastEmitted.current = JSON.stringify(obj);
        setEntries(next);
        onChange(obj);
    };

    return (
        <div className="flex flex-col gap-1">
            {entries.map((entry, i) => (
                <div key={entry.id} className="flex items-start gap-1">
                    <input
                        placeholder="key"
                        className={`${inputCls} w-28`}
                        value={entry.k}
                        onChange={(e) => { const next = [...entries]; next[i] = { ...entries[i], k: e.target.value }; emit(next); }}
                    />
                    <ValueEditor value={entry.v} onChange={(nv) => { const next = [...entries]; next[i] = { ...entries[i], v: nv }; emit(next); }} />
                    <TypeSelect name={entry.k || `#${i}`} type={typeOf(entry.v)} onChange={(t) => { const next = [...entries]; next[i] = { ...entries[i], v: defaultFor(t) }; emit(next); }} />
                    <button aria-label={`remove ${entry.k || `#${i}`}`} className="px-1 text-gray-400 hover:text-red-500" onClick={() => emit(entries.filter((_, j) => j !== i))}>×</button>
                </div>
            ))}
            <button className="text-xs text-sky-600 text-left mt-1" onClick={() => emit([...entries, { id: nextRowId(), k: '', v: '' }])}>+ add field</button>
        </div>
    );
}

type ArrItem = { id: string; v: any };

function ArrayEditor({ value, onChange }: { value: any[]; onChange: (v: any) => void }) {
    const [items, setItems] = useState<ArrItem[]>(() => value.map((v) => ({ id: nextRowId(), v })));
    const lastEmitted = useRef<string>(JSON.stringify(value));

    const extSig = JSON.stringify(value);
    useEffect(() => {
        if (extSig !== lastEmitted.current) {
            lastEmitted.current = extSig;
            setItems(value.map((v) => ({ id: nextRowId(), v })));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [extSig]);

    const emit = (next: ArrItem[]) => {
        const arr = next.map((item) => item.v);
        lastEmitted.current = JSON.stringify(arr);
        setItems(next);
        onChange(arr);
    };

    return (
        <div className="flex flex-col gap-1">
            {items.map((item, i) => (
                <div key={item.id} className="flex items-start gap-1">
                    <span className="text-xs text-gray-400 w-6 pt-1">{i}</span>
                    <ValueEditor value={item.v} onChange={(nv) => { const next = [...items]; next[i] = { ...items[i], v: nv }; emit(next); }} />
                    <TypeSelect name={`#${i}`} type={typeOf(item.v)} onChange={(t) => { const next = [...items]; next[i] = { ...items[i], v: defaultFor(t) }; emit(next); }} />
                    <button aria-label={`remove #${i}`} className="px-1 text-gray-400 hover:text-red-500" onClick={() => emit(items.filter((_, j) => j !== i))}>×</button>
                </div>
            ))}
            <button className="text-xs text-sky-600 text-left mt-1" onClick={() => emit([...items, { id: nextRowId(), v: '' }])}>+ add item</button>
        </div>
    );
}
