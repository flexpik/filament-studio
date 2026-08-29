import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { InspectorPanel } from '../inspector/InspectorPanel';

const node: any = { id: 'op_a', type: 'operation', data: { key: 'a', operationType: 'log_message', config: { level: 'info', message: '' } } };

describe('InspectorPanel', () => {
    it('renders log_message fields and pushes config changes upward', () => {
        const onConfigChange = vi.fn();
        const { getByLabelText } = render(
            <InspectorPanel node={node} meta={{ triggers: [], operations: [], collections: [] }} onConfigChange={onConfigChange} onClose={() => {}} />,
        );
        fireEvent.change(getByLabelText('Message'), { target: { value: 'hello' } });
        expect(onConfigChange).toHaveBeenCalledWith({ level: 'info', message: 'hello' });
    });
});
