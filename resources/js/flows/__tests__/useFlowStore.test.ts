import { describe, it, expect } from 'vitest';
import { createFlowStore } from '../state/useFlowStore';

describe('useFlowStore', () => {
    it('loads a graph and marks not dirty', () => {
        const s = createFlowStore();
        s.getState().loadGraph({ nodes: [{ id: 'a' } as any], edges: [] });
        expect(s.getState().nodes).toHaveLength(1);
        expect(s.getState().dirty).toBe(false);
    });

    it('updateNodeConfig marks dirty', () => {
        const s = createFlowStore();
        s.getState().loadGraph({ nodes: [{ id: 'a', data: { config: {} } } as any], edges: [] });
        s.getState().updateNodeConfig('a', { greeting: 'hi' });
        expect((s.getState().nodes[0] as any).data.config).toEqual({ greeting: 'hi' });
        expect(s.getState().dirty).toBe(true);
    });

    it('addNode and removeNode adjust the array and dirty', () => {
        const s = createFlowStore();
        s.getState().addNode({ id: 'a' } as any);
        expect(s.getState().nodes).toHaveLength(1);
        s.getState().removeNode('a');
        expect(s.getState().nodes).toHaveLength(0);
    });
});

describe('removeEdge', () => {
    it('removes the edge with the given id and marks dirty', () => {
        const store = createFlowStore();
        store.getState().loadFlow({
            draft_graph: {
                nodes: [],
                edges: [
                    { id: 'e1', source: 'a', target: 'b' } as any,
                    { id: 'e2', source: 'b', target: 'c' } as any,
                ],
            },
            draft_updated_at: null,
            published_version: null,
        });
        store.getState().removeEdge('e1');
        const { edges, dirty } = store.getState();
        expect(edges.map((e) => e.id)).toEqual(['e2']);
        expect(dirty).toBe(true);
    });
});

describe('useFlowStore versioning', () => {
    it('tracks publishedVersion and draftSavedAt separately', () => {
        const store = createFlowStore();
        store.getState().loadFlow({
            draft_graph: { nodes: [], edges: [] },
            draft_updated_at: '2026-05-11T00:00:00Z',
            published_version: { version: 3, published_at: '2026-05-10T00:00:00Z' },
        } as any);
        const s = store.getState();
        expect(s.publishedVersion?.version).toBe(3);
        expect(s.draftSavedAt).toBe('2026-05-11T00:00:00Z');
        expect(s.dirty).toBe(false);
    });

    it('loadFlow with null draft_graph uses empty graph', () => {
        const store = createFlowStore();
        store.getState().loadFlow({
            draft_graph: null,
            draft_updated_at: null,
            published_version: null,
        });
        const s = store.getState();
        expect(s.nodes).toHaveLength(0);
        expect(s.edges).toHaveLength(0);
        expect(s.publishedVersion).toBeNull();
        expect(s.draftSavedAt).toBeNull();
        expect(s.dirty).toBe(false);
    });
});
