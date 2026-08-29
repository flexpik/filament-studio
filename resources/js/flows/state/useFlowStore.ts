import { create, StoreApi, UseBoundStore } from 'zustand';
import type { Edge, Node } from '@xyflow/react';

type Graph = { nodes: Node[]; edges: Edge[] };

type PublishedVersion = { version: number; published_at: string };

type FlowState = {
    nodes: Node[];
    edges: Edge[];
    selectedNodeId: string | null;
    dirty: boolean;
    publishedVersion: PublishedVersion | null;
    draftSavedAt: string | null;
    pausedRunId: string | null;
    lastStepNodeId: string | null;
    loadGraph: (g: Graph) => void;
    loadFlow: (resp: {
        draft_graph: Graph | null;
        draft_updated_at: string | null;
        published_version: PublishedVersion | null;
    }) => void;
    setNodes: (nodes: Node[]) => void;
    setEdges: (edges: Edge[]) => void;
    addNode: (n: Node) => void;
    removeNode: (id: string) => void;
    removeEdge: (id: string) => void;
    selectNode: (id: string | null) => void;
    updateNodeConfig: (id: string, patch: Record<string, unknown>) => void;
    markClean: () => void;
    setPausedRun: (runId: string | null, nodeId: string | null) => void;
    clearPausedRun: () => void;
};

export function createFlowStore(): UseBoundStore<StoreApi<FlowState>> {
    return create<FlowState>((set) => ({
        nodes: [],
        edges: [],
        selectedNodeId: null,
        dirty: false,
        publishedVersion: null,
        draftSavedAt: null,
        pausedRunId: null,
        lastStepNodeId: null,
        loadGraph: (g) => set({ nodes: g.nodes, edges: g.edges, dirty: false }),
        loadFlow: (resp) => {
            const graph = resp.draft_graph ?? { nodes: [], edges: [] };
            set({
                nodes: graph.nodes ?? [],
                edges: graph.edges ?? [],
                dirty: false,
                publishedVersion: resp.published_version ?? null,
                draftSavedAt: resp.draft_updated_at ?? null,
            });
        },
        setNodes: (nodes) => set({ nodes, dirty: true }),
        setEdges: (edges) => set({ edges, dirty: true }),
        addNode: (n) => set((s) => ({ nodes: [...s.nodes, n], dirty: true })),
        removeEdge: (id) => set((s) => ({
            edges: s.edges.filter((e) => e.id !== id),
            dirty: true,
        })),
        removeNode: (id) => set((s) => ({
            nodes: s.nodes.filter((x) => x.id !== id),
            edges: s.edges.filter((e) => e.source !== id && e.target !== id),
            dirty: true,
        })),
        selectNode: (id) => set({ selectedNodeId: id }),
        updateNodeConfig: (id, patch) => set((s) => ({
            nodes: s.nodes.map((n) => n.id === id
                ? { ...n, data: { ...(n.data as any), config: { ...((n.data as any).config ?? {}), ...patch } } }
                : n),
            dirty: true,
        })),
        markClean: () => set({ dirty: false }),
        setPausedRun: (runId, nodeId) => set({ pausedRunId: runId, lastStepNodeId: nodeId }),
        clearPausedRun: () => set({ pausedRunId: null, lastStepNodeId: null }),
    }));
}

export const useFlowStore = createFlowStore();
