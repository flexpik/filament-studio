import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { NodePalette } from '../palette/NodePalette';

const meta = {
    triggers: [{ key: 'manual', label: 'Manual' }],
    operations: [{ key: 'send_email', label: 'Send Email' }],
};

describe('NodePalette', () => {
    it('renders an icon for each item', () => {
        const { container } = render(<NodePalette meta={meta as any} onAdd={() => {}} />);
        expect(container.querySelectorAll('svg').length).toBeGreaterThanOrEqual(2);
    });

    it('still adds on click', () => {
        const onAdd = vi.fn();
        const { getByText } = render(<NodePalette meta={meta as any} onAdd={onAdd} />);
        fireEvent.click(getByText('Send Email'));
        expect(onAdd).toHaveBeenCalledWith('operation', 'send_email', 'Send Email');
    });

    it('sets dataTransfer on drag start', () => {
        const { getByText } = render(<NodePalette meta={meta as any} onAdd={() => {}} />);
        const setData = vi.fn();
        const item = getByText('Send Email').closest('[draggable="true"]')!;
        fireEvent.dragStart(item, { dataTransfer: { setData, effectAllowed: '' } });
        expect(setData).toHaveBeenCalledWith(
            'application/x-flow-node',
            JSON.stringify({ type: 'operation', key: 'send_email', label: 'Send Email' }),
        );
    });
});
