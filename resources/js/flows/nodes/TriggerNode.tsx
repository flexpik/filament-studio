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
