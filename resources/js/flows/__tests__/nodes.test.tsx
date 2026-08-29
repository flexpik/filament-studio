import { describe, it, expect } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { ReactFlowProvider } from '@xyflow/react';
import { TriggerNode } from '../nodes/TriggerNode';
import { OperationNode } from '../nodes/OperationNode';
import { useFlowStore } from '../state/useFlowStore';

const baseNodeProps = {
    selected: false,
    type: 'trigger',
    dragging: false,
    zIndex: 0,
    isConnectable: true,
    positionAbsoluteX: 0,
    positionAbsoluteY: 0,
    selectable: true,
    deletable: true,
    draggable: true,
} as const;

const wrap = (ui: React.ReactNode) => render(<ReactFlowProvider>{ui}</ReactFlowProvider>);

describe('TriggerNode', () => {
    it('shows the trigger type label', () => {
        const { getByText } = wrap(
            <TriggerNode id="trigger" data={{ triggerType: 'manual' }} {...baseNodeProps} />
        );
        expect(getByText(/manual/i)).toBeInTheDocument();
    });
});

describe('OperationNode', () => {
    it('renders label', () => {
        const { getByText } = wrap(
            <OperationNode
                id="op_a"
                data={{ key: 'a', operationType: 'send_email', label: 'Send Email' }}
                {...baseNodeProps}
                type="operation"
            />
        );
        expect(getByText('Send Email')).toBeInTheDocument();
    });
});

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
