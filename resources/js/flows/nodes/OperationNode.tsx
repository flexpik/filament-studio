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
