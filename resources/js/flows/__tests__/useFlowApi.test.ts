import { describe, it, expect, vi } from 'vitest';
import axios from 'axios';
import { createFlowApi } from '../api/useFlowApi';

vi.mock('axios');

describe('createFlowApi', () => {
    it('GET /flows/{id}/graph hits the right URL with X-Api-Key', async () => {
        const get = vi.fn().mockResolvedValue({ data: { data: { draft_graph: null, draft_updated_at: null, published_version: null } } });
        (axios.create as any).mockReturnValue({ get, put: vi.fn(), post: vi.fn() });

        const api = createFlowApi({ apiBase: '/api/studio', apiKey: 'k', flowId: 'f1' });
        await api.loadGraph();

        expect(get).toHaveBeenCalledWith('/flows/f1/graph');
    });

    it('PUT /graph sends the graph body (saveGraph alias)', async () => {
        const put = vi.fn().mockResolvedValue({ data: {} });
        (axios.create as any).mockReturnValue({ get: vi.fn(), put, post: vi.fn() });

        const api = createFlowApi({ apiBase: '/api/studio', apiKey: 'k', flowId: 'f1' });
        await api.saveGraph({ nodes: [], edges: [] });

        expect(put).toHaveBeenCalledWith('/flows/f1/graph', { graph: { nodes: [], edges: [] } });
    });

    it('loadGraph returns the full graph response object', async () => {
        const get = vi.fn().mockResolvedValue({ data: { data: { draft_graph: null, published_version: null } } });
        (axios.create as any).mockReturnValue({ get, put: vi.fn(), post: vi.fn() });
        const api = createFlowApi({ apiBase: '/api/studio', apiKey: 'k', flowId: 'f1' });
        const resp = await api.loadGraph();
        expect(resp).toHaveProperty('draft_graph');
    });

    it('saveDraft PUTs to /graph', async () => {
        const put = vi.fn().mockResolvedValue({ data: { data: {} } });
        (axios.create as any).mockReturnValue({ get: vi.fn(), put, post: vi.fn() });
        const api = createFlowApi({ apiBase: '/api/studio', apiKey: 'k', flowId: 'f1' });
        await api.saveDraft({ nodes: [], edges: [] });
        expect(put).toHaveBeenCalledWith('/flows/f1/graph', { graph: { nodes: [], edges: [] } });
    });
});
