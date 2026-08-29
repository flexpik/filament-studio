import React, { useEffect, useMemo, useState, useCallback } from 'react';
import { ReactFlow, Background, Controls, ReactFlowProvider, addEdge, applyEdgeChanges, applyNodeChanges, useReactFlow } from '@xyflow/react';
import type { Edge, Node } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { createRoot } from 'react-dom/client';

import { TriggerNode } from './nodes/TriggerNode';
import { OperationNode } from './nodes/OperationNode';
import { BranchEdge } from './edges/BranchEdge';
import { useFlowStore } from './state/useFlowStore';
import { createFlowApi } from './api/useFlowApi';
import type { FlowApi } from './api/useFlowApi';
import { Toolbar } from './toolbar/Toolbar';
import { NodePalette, FLOW_NODE_MIME } from './palette/NodePalette';
import { InspectorPanel } from './inspector/InspectorPanel';
import { TestRunPanel } from './testrun/TestRunPanel';

const nodeTypes = { trigger: TriggerNode, operation: OperationNode };
const edgeTypes = { branch: BranchEdge };

export function createNode(type: 'trigger' | 'operation', key: string, label: string, position: { x: number; y: number }): Node {
    return {
        id: `${key}-${Date.now()}`,
        type,
        position,
        selected: true,
        data: { label, operationType: key, triggerType: key, config: {} },
    };
}

function useFilamentColorMode(): 'light' | 'dark' {
    const [mode, setMode] = useState<'light' | 'dark'>(
        () => document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    );
    useEffect(() => {
        const observer = new MutationObserver(() => {
            setMode(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        });
        observer.observe(document.documentElement, { attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);
    return mode;
}

function CanvasInner({ api, flowName, meta }: { api: FlowApi; flowName: string; meta: any }) {
    const { nodes, edges, dirty, selectedNodeId, loadFlow, setNodes, setEdges, selectNode, updateNodeConfig, markClean, addNode } = useFlowStore();
    const [saving, setSaving] = useState(false);
    const [showTestRun, setShowTestRun] = useState(false);
    const [paletteOpen, setPaletteOpen] = useState(false);
    const [fullscreen, setFullscreen] = useState(false);
    const colorMode = useFilamentColorMode();
    const { getViewport, setCenter, screenToFlowPosition } = useReactFlow();

    useEffect(() => {
        api.loadGraph().then(loadFlow).catch(console.error);
    }, [api, loadFlow]);

    useEffect(() => {
        if (!fullscreen) return;
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setFullscreen(false); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [fullscreen]);

    useEffect(() => {
        if (!dirty) return;
        const t = setTimeout(async () => {
            setSaving(true);
            try {
                await api.saveGraph({ nodes, edges });
                markClean();
            } catch (err) {
                console.error('[autosave] failed:', err);
            } finally {
                setSaving(false);
            }
        }, 2000);
        return () => clearTimeout(t);
    }, [dirty, nodes, edges, api, markClean]);

    const onNodesChange = useCallback((changes: any) => setNodes(applyNodeChanges(changes, nodes)), [nodes, setNodes]);
    const onEdgesChange = useCallback((changes: any) => setEdges(applyEdgeChanges(changes, edges)), [edges, setEdges]);
    const onConnect = useCallback((conn: any) => setEdges(addEdge({ ...conn, type: 'branch' }, edges)), [edges, setEdges]);

    const addAndFocus = useCallback((type: 'trigger' | 'operation', key: string, label: string, position: { x: number; y: number }) => {
        const node = createNode(type, key, label, position);
        addNode(node);
        selectNode(node.id);
        setCenter(position.x + 90, position.y + 25, { zoom: getViewport().zoom, duration: 400 });
    }, [addNode, selectNode, setCenter, getViewport]);

    const handleAddNode = useCallback((type: 'trigger' | 'operation', key: string, label: string) => {
        const { x, y, zoom } = getViewport();
        const cx = (-x + window.innerWidth / 2) / zoom;
        const cy = (-y + window.innerHeight / 2) / zoom;
        addAndFocus(type, key, label, { x: cx + (Math.random() - 0.5) * 60, y: cy + (Math.random() - 0.5) * 60 });
    }, [getViewport, addAndFocus]);

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        const raw = e.dataTransfer.getData(FLOW_NODE_MIME);
        if (!raw) return;
        let parsed: { type: 'trigger' | 'operation'; key: string; label: string };
        try {
            parsed = JSON.parse(raw);
        } catch {
            return;
        }
        const position = screenToFlowPosition({ x: e.clientX, y: e.clientY });
        addAndFocus(parsed.type, parsed.key, parsed.label, position);
    }, [screenToFlowPosition, addAndFocus]);

    const selected = nodes.find((n) => n.id === selectedNodeId) ?? null;

    return (
        <div className={fullscreen ? 'fixed inset-0 z-40 flex flex-col bg-white dark:bg-gray-900' : 'flex flex-col h-full'}>
            <Toolbar
                flowName={flowName}
                dirty={dirty}
                saving={saving}
                paletteOpen={paletteOpen}
                fullscreen={fullscreen}
                onToggleFullscreen={() => setFullscreen(v => !v)}
                onTogglePalette={() => setPaletteOpen(v => !v)}
                onSave={async () => {
                    setSaving(true);
                    try { await api.saveGraph({ nodes, edges }); markClean(); }
                    catch (err) { console.error('[save] failed:', err); }
                    finally { setSaving(false); }
                }}
                onPublish={async () => {
                    try { await api.publish('canvas publish'); }
                    catch (err) { console.error('[publish] failed:', err); }
                }}
                onTestRun={() => setShowTestRun(true)}
            />
            <div className="flex flex-1 min-h-0 relative">
                {paletteOpen && (
                    <div className="absolute inset-y-0 left-0 z-30 w-64 shadow-xl md:relative md:z-auto md:w-56 md:shadow-none">
                        <NodePalette meta={meta} onAdd={handleAddNode} />
                    </div>
                )}
                <div className="flex-1" onDrop={handleDrop} onDragOver={handleDragOver}>
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        nodeTypes={nodeTypes}
                        edgeTypes={edgeTypes}
                        colorMode={colorMode}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={(_, n) => selectNode(n.id)}
                        onPaneClick={() => selectNode(null)}
                        fitView
                    >
                        <Background />
                        <Controls />
                    </ReactFlow>
                </div>
                {selected && (
                    <div className="absolute inset-y-0 right-0 z-30 w-full max-w-sm shadow-xl md:relative md:z-auto md:max-w-none md:w-96 md:shadow-none">
                        <InspectorPanel
                            node={selected}
                            meta={meta}
                            onConfigChange={(config) => updateNodeConfig(selected.id, config)}
                            onClose={() => selectNode(null)}
                        />
                    </div>
                )}
                {(paletteOpen || selected) && (
                    <div
                        aria-label="close panels"
                        className="md:hidden absolute inset-0 bg-black/30 z-20"
                        onClick={() => { setPaletteOpen(false); selectNode(null); }}
                    />
                )}
            </div>
            {showTestRun && <TestRunPanel api={api} onClose={() => setShowTestRun(false)} />}
        </div>
    );
}

export function FlowCanvasApp({ flowId, apiBase, apiKey, flowName }: {
    flowId: string; apiBase: string; apiKey: string; flowName: string;
}) {
    const api: FlowApi = useMemo(() => createFlowApi({ apiBase, apiKey, flowId }), [apiBase, apiKey, flowId]);
    const [meta, setMeta] = useState<any>({ triggers: [], operations: [] });

    useEffect(() => {
        api.loadMeta().then(setMeta).catch(console.error);
    }, [api]);

    return (
        <ReactFlowProvider>
            <CanvasInner api={api} flowName={flowName} meta={meta} />
        </ReactFlowProvider>
    );
}

const root = document.getElementById('flow-canvas-root');
if (root) {
    const key = (document.querySelector('meta[name="studio-api-key"]') as HTMLMetaElement | null)?.content ?? '';
    createRoot(root).render(
        <FlowCanvasApp
            flowId={root.dataset.flowId ?? ''}
            apiBase={root.dataset.apiBase ?? '/api/studio'}
            apiKey={key}
            flowName={root.dataset.flowName ?? ''}
        />,
    );
}
