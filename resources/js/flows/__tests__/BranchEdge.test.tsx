import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { BranchEdge } from '../edges/BranchEdge';
import { useFlowStore } from '../state/useFlowStore';

// Mock EdgeLabelRenderer to render children directly (no portal needed in JSDOM)
vi.mock('@xyflow/react', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@xyflow/react')>();
    return {
        ...actual,
        EdgeLabelRenderer: ({ children }: { children: React.ReactNode }) => <>{children}</>,
    };
});

const defaultProps = {
    id: 'e1',
    source: 'a',
    target: 'b',
    sourceX: 0,
    sourceY: 0,
    targetX: 200,
    targetY: 0,
    selected: false,
    animated: false,
    data: {},
    style: {},
    markerEnd: '',
    markerStart: '',
    sourceHandleId: null,
    targetHandleId: null,
    interactionWidth: 20,
    label: undefined,
    labelStyle: {},
    labelShowBg: false,
    labelBgStyle: {},
    labelBgPadding: [2, 4] as [number, number],
    labelBgBorderRadius: 2,
    sourcePosition: 'bottom' as any,
    targetPosition: 'top' as any,
    pathOptions: undefined,
};

describe('BranchEdge delete', () => {
    beforeEach(() => {
        useFlowStore.setState({
            nodes: [],
            edges: [{ id: 'e1', source: 'a', target: 'b', type: 'branch' } as any],
            selectedNodeId: null,
            dirty: false,
        });
    });

    it('removes the edge from the store when × is clicked', () => {
        const { getByLabelText } = render(
            <svg>
                <BranchEdge {...defaultProps} />
            </svg>
        );
        fireEvent.click(getByLabelText('delete edge'));
        expect(useFlowStore.getState().edges).toHaveLength(0);
    });

    it('shows the × (opacity-100) when the edge is selected, hidden otherwise', () => {
        const hidden = render(<svg><BranchEdge {...defaultProps} selected={false} /></svg>);
        expect(hidden.getByLabelText('delete edge').getAttribute('class')).toContain('opacity-0');
        hidden.unmount();
        const shown = render(<svg><BranchEdge {...defaultProps} selected={true} /></svg>);
        expect(shown.getByLabelText('delete edge').getAttribute('class')).toContain('opacity-100');
    });
});
