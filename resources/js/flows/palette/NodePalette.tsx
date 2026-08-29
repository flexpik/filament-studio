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
        <div className="w-full h-full flex flex-col border-r bg-gray-50 dark:bg-gray-900 dark:border-gray-700 overflow-y-auto">
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
