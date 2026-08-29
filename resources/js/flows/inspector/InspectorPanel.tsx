import React from 'react';
import type { Node } from '@xyflow/react';
import { FieldRenderer } from './FieldRenderer';
import { operationConfigSchemas, triggerConfigSchemas } from './configSchemas';

export function InspectorPanel({ node, meta, onConfigChange, onClose }: {
    node: Node;
    meta: { triggers: any[]; operations: any[]; collections: any[]; flows?: any[] };
    onConfigChange: (config: Record<string, unknown>) => void;
    onClose: () => void;
}) {
    const data = node.data as any;
    const isTrigger = node.type === 'trigger';
    const key = isTrigger ? data.triggerType : data.operationType;
    const fields = (isTrigger ? triggerConfigSchemas : operationConfigSchemas)[key] ?? [];

    return (
        <aside className="w-full h-full border-l bg-white dark:bg-gray-900 p-4 overflow-y-auto">
            <div className="flex items-center justify-between mb-4">
                <h3 className="font-semibold">{isTrigger ? 'Trigger' : 'Operation'} — {key}</h3>
                <button onClick={onClose} className="text-sm">×</button>
            </div>
            <div className="space-y-3">
                {fields.map(({ name, schema }) => {
                    let injectedSchema = schema;
                    if ((schema as any).type === 'collection_select') {
                        injectedSchema = { ...schema, options: meta.collections.map((c: any) => ({ slug: c.slug, name: c.name })) } as any;
                    }
                    if ((schema as any).type === 'flow_select') {
                        injectedSchema = { ...schema, options: (meta.flows ?? []).map((f: any) => ({ id: f.id, name: f.name })) } as any;
                    }
                    return (
                        <FieldRenderer
                            key={`${node.id}:${name}`}
                            name={name}
                            schema={injectedSchema}
                            value={data.config?.[name]}
                            onChange={(v) => onConfigChange({ ...(data.config ?? {}), [name]: v })}
                        />
                    );
                })}
            </div>
        </aside>
    );
}
