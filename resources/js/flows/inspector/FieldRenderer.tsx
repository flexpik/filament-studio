import React, { useState } from 'react';
import { ObjectBuilder } from './ObjectBuilder';

const controlClass = 'border rounded px-2 py-1 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100';

export type FieldSchema =
    | { type: 'string'; label: string; placeholder?: string }
    | { type: 'text'; label: string; placeholder?: string }
    | { type: 'enum'; label: string; options: string[] }
    | { type: 'boolean'; label: string }
    | { type: 'collection_select'; label: string; options: { slug: string; name: string }[] }
    | { type: 'flow_select'; label: string; options: { id: string; name: string }[] }
    | { type: 'json'; label: string }
    | { type: 'key_value'; label: string };

export function FieldRenderer({ name, schema, value, onChange }: {
    name: string; schema: FieldSchema; value: any; onChange: (v: any) => void;
}) {
    const id = `field-${name}`;

    switch (schema.type) {
        case 'string':
            return (
                <label htmlFor={id} className="flex flex-col gap-1 text-sm">
                    <span>{schema.label}</span>
                    <input id={id} aria-label={schema.label} className={controlClass} value={value ?? ''} placeholder={schema.placeholder} onChange={(e) => onChange(e.target.value)} />
                </label>
            );
        case 'text':
            return (
                <label htmlFor={id} className="flex flex-col gap-1 text-sm">
                    <span>{schema.label}</span>
                    <textarea id={id} aria-label={schema.label} className={`${controlClass} font-mono`} rows={4} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
                </label>
            );
        case 'enum':
            return (
                <label htmlFor={id} className="flex flex-col gap-1 text-sm">
                    <span>{schema.label}</span>
                    <select id={id} aria-label={schema.label} className={controlClass} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                        {schema.options.map((o) => <option key={o} value={o}>{o}</option>)}
                    </select>
                </label>
            );
        case 'boolean':
            return (
                <label htmlFor={id} className="flex items-center gap-2 text-sm">
                    <input id={id} aria-label={schema.label} type="checkbox" checked={!!value} onChange={(e) => onChange(e.target.checked)} />
                    <span>{schema.label}</span>
                </label>
            );
        case 'collection_select':
        case 'flow_select': {
            const opts = (schema as any).options as { slug?: string; id?: string; name: string }[];
            return (
                <label htmlFor={id} className="flex flex-col gap-1 text-sm">
                    <span>{schema.label}</span>
                    <select id={id} aria-label={schema.label} className={controlClass} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                        <option value="">— select —</option>
                        {opts.map((o) => {
                            const v = o.slug ?? o.id ?? '';
                            return <option key={v} value={v}>{o.name}</option>;
                        })}
                    </select>
                </label>
            );
        }
        case 'json':
            return <JsonField id={id} label={schema.label} value={value} onChange={onChange} />;
        case 'key_value':
            return <KeyValueEditor value={value ?? {}} onChange={onChange} label={schema.label} />;
    }
}

function KeyValueEditor({ value, onChange, label }: { value: Record<string, string>; onChange: (v: Record<string, string>) => void; label: string }) {
    const entries = Object.entries(value);
    return (
        <div className="text-sm">
            <div className="mb-1">{label}</div>
            {entries.map(([k, v], i) => (
                <div key={k + String(i)} className="flex gap-2 mb-1">
                    <input className={`${controlClass} w-1/3`} value={k} onChange={(e) => {
                        const next = { ...value }; delete next[k]; next[e.target.value] = v; onChange(next);
                    }} />
                    <input className={`${controlClass} flex-1`} value={v} onChange={(e) => onChange({ ...value, [k]: e.target.value })} />
                </div>
            ))}
            <button className="text-xs text-sky-600" onClick={() => onChange({ ...value, '': '' })}>+ add row</button>
        </div>
    );
}

function JsonField({ id, label, value, onChange }: { id: string; label: string; value: any; onChange: (v: any) => void }) {
    const [mode, setMode] = useState<'builder' | 'json'>('builder');
    return (
        <div className="flex flex-col gap-1 text-sm">
            <div className="flex items-center justify-between">
                <span>{label}</span>
                <div className="inline-flex rounded border text-xs overflow-hidden dark:border-gray-600">
                    <button type="button" aria-pressed={mode === 'builder'} className={`px-2 py-0.5 ${mode === 'builder' ? 'bg-sky-500 text-white' : ''}`} onClick={() => setMode('builder')}>Builder</button>
                    <button type="button" aria-pressed={mode === 'json'} className={`px-2 py-0.5 ${mode === 'json' ? 'bg-sky-500 text-white' : ''}`} onClick={() => setMode('json')}>{'{} JSON'}</button>
                </div>
            </div>
            {mode === 'builder'
                ? <ObjectBuilder value={value ?? {}} onChange={onChange} />
                : <textarea id={id} aria-label={`${label} (raw JSON)`} className={`${controlClass} font-mono`} rows={6}
                    value={typeof value === 'string' ? value : JSON.stringify(value ?? {}, null, 2)}
                    onChange={(e) => { try { onChange(JSON.parse(e.target.value)); } catch { onChange(e.target.value); } }} />}
        </div>
    );
}
