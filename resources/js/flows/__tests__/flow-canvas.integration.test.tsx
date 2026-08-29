import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, waitFor, fireEvent } from '@testing-library/react';
import { FlowCanvasApp, createNode } from '../flow-canvas';
import * as apiMod from '../api/useFlowApi';
import { useFlowStore } from '../state/useFlowStore';

describe('FlowCanvasApp integration', () => {
    beforeEach(() => {
        useFlowStore.setState({ nodes: [], edges: [], selectedNodeId: null, dirty: false });
    });

    it('mounts, loads graph, debounces autosave', async () => {
        const saveGraph = vi.fn().mockResolvedValue(undefined);
        vi.spyOn(apiMod, 'createFlowApi').mockReturnValue({
            loadGraph: vi.fn().mockResolvedValue({ draft_graph: { nodes: [{ id: 'trigger', type: 'trigger', position: { x: 0, y: 0 }, data: { triggerType: 'manual' } }], edges: [] }, draft_updated_at: null, published_version: null }),
            saveGraph,
            publish: vi.fn(),
            loadMeta: vi.fn().mockResolvedValue({ triggers: [], operations: [], collections: [] }),
            testRun: vi.fn(),
            pollRun: vi.fn(),
        });

        const { container } = render(<FlowCanvasApp flowId="f" apiBase="/api/studio" apiKey="" flowName="X" />);

        await waitFor(() => expect(container.querySelector('.react-flow')).toBeTruthy(), { timeout: 3000 });

        // Autosave is debounced — no save should fire on initial load.
        expect(saveGraph).not.toHaveBeenCalled();
    });
});

describe('mobile drawer backdrop', () => {
    it('shows a backdrop when the palette opens and closes the palette on backdrop click', async () => {
        vi.spyOn(apiMod, 'createFlowApi').mockReturnValue({
            loadGraph: vi.fn().mockResolvedValue({ draft_graph: null, draft_updated_at: null, published_version: null }),
            saveGraph: vi.fn().mockResolvedValue(undefined),
            publish: vi.fn(),
            loadMeta: vi.fn().mockResolvedValue({ triggers: [], operations: [], collections: [] }),
            testRun: vi.fn(),
            pollRun: vi.fn(),
        } as any);

        const { getByText, getByLabelText, queryByLabelText, queryByPlaceholderText } = render(
            <FlowCanvasApp flowId="f" apiBase="/api/studio" apiKey="" flowName="X" />
        );

        // No panel open → no backdrop.
        expect(queryByLabelText('close panels')).toBeNull();

        // Open the palette via the toolbar "Add" button.
        fireEvent.click(getByText('Add'));
        expect(queryByPlaceholderText('Search nodes…')).not.toBeNull();
        const backdrop = getByLabelText('close panels');

        // Clicking the backdrop closes the palette.
        fireEvent.click(backdrop);
        expect(queryByPlaceholderText('Search nodes…')).toBeNull();
        expect(queryByLabelText('close panels')).toBeNull();
    });
});

describe('createNode', () => {
    it('builds a node at the given position with config data', () => {
        const n = createNode('operation', 'send_email', 'Send Email', { x: 100, y: 50 });
        expect(n.type).toBe('operation');
        expect(n.position).toEqual({ x: 100, y: 50 });
        expect((n.data as any).label).toBe('Send Email');
        expect((n.data as any).operationType).toBe('send_email');
        expect((n.data as any).config).toEqual({});
        expect(n.id).toContain('send_email');
        expect(n.selected).toBe(true);
    });
});
