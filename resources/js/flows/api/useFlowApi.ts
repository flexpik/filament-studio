import axios, { AxiosInstance } from 'axios';

export type Graph = { nodes: any[]; edges: any[] };

export type FlowApi = {
    loadGraph: () => Promise<{ draft_graph: any; draft_updated_at: string | null; published_version: any }>;
    saveDraft: (graph: Graph) => Promise<{ draft_graph: any; draft_updated_at: string | null }>;
    saveGraph: (graph: Graph) => Promise<void>;
    publish: (changeSummary?: string) => Promise<any>;
    listVersions: () => Promise<any[]>;
    getVersion: (versionId: string) => Promise<any>;
    rollback: (versionId: string) => Promise<any>;
    loadMeta: () => Promise<{ triggers: any[]; operations: any[]; collections: any[] }>;
    testRun: (args: { payload: any; dryRun?: boolean; stepThrough?: boolean }) => Promise<{ id: string }>;
    pollRun: (runId: string) => Promise<any>;
    step: (runId: string, action?: string) => Promise<any>;
};

export function createFlowApi(opts: { apiBase: string; apiKey: string; flowId: string }): FlowApi {
    const http: AxiosInstance = axios.create({
        baseURL: opts.apiBase,
        headers: { 'X-Api-Key': opts.apiKey, Accept: 'application/json' },
    });

    return {
        loadGraph: async () => (await http.get(`/flows/${opts.flowId}/graph`)).data.data,
        saveDraft: async (graph) => (await http.put(`/flows/${opts.flowId}/graph`, { graph })).data.data,
        saveGraph: async (graph) => { await http.put(`/flows/${opts.flowId}/graph`, { graph }); },
        publish: async (change_summary) => (await http.post(`/flows/${opts.flowId}/publish`, { change_summary })).data,
        listVersions: async () => (await http.get(`/flows/${opts.flowId}/versions`)).data.data,
        getVersion: async (versionId) => (await http.get(`/flows/${opts.flowId}/versions/${versionId}`)).data.data,
        rollback: async (versionId) => (await http.post(`/flows/${opts.flowId}/versions/${versionId}/rollback`)).data,
        loadMeta: async () => {
            const [t, o, c] = await Promise.all([
                http.get('/flows/meta/triggers'),
                http.get('/flows/meta/operations'),
                http.get('/flows/meta/collections'),
            ]);
            return { triggers: t.data.data, operations: o.data.data, collections: c.data.data };
        },
        testRun: async ({ payload, dryRun = false, stepThrough = false }) => (await http.post(`/flows/${opts.flowId}/test`, { payload, dry_run: dryRun, step_through: stepThrough })).data.data,
        pollRun: async (runId) => (await http.get(`/flows/${opts.flowId}/runs/${runId}`)).data.data,
        step: async (runId, action?) => {
            const body = action ? { action } : {};
            return (await http.post(`/flows/runs/${runId}/step`, body)).data.data;
        },
    };
}
