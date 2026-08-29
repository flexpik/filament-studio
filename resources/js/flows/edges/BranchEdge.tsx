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
                    className={`nodrag nopan absolute w-5 h-5 rounded-full bg-gray-700 text-white text-xs leading-none flex items-center justify-center hover:opacity-100 focus:opacity-100 transition-opacity ${props.selected ? 'opacity-100' : 'opacity-0'}`}
                    style={{ transform: `translate(-50%, -50%) translate(${labelX}px, ${labelY}px)`, pointerEvents: 'all' }}
                    onClick={(e) => { e.stopPropagation(); useFlowStore.getState().removeEdge(id); }}
                >×</button>
            </EdgeLabelRenderer>
        </>
    );
}
