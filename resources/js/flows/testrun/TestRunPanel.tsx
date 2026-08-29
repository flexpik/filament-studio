import React, { useState, useRef } from 'react';
import { useFlowStore } from '../state/useFlowStore';

export function TestRunPanel({ api, onClose, pollMs = 1500 }: {
    api: {
        testRun: (args: { payload: any; dryRun?: boolean; stepThrough?: boolean }) => Promise<{ id: string }>;
        pollRun: (id: string) => Promise<any>;
        step?: (runId: string, action?: string) => Promise<any>;
    };
    onClose: () => void; pollMs?: number;
}) {
    const [payload, setPayload] = useState('{}');
    const [dryRun, setDryRun] = useState(false);
    const [stepThrough, setStepThrough] = useState(false);
    const [run, setRun] = useState<any | null>(null);
    const [pausedRun, setPausedRun] = useState<any | null>(null);
    const [busy, setBusy] = useState(false);
    const cancelledRef = useRef(false);
    const storePausedRun = useFlowStore((s) => s.setPausedRun);
    const storeClearPaused = useFlowStore((s) => s.clearPausedRun);

    function handleClose() {
        cancelledRef.current = true;
        onClose();
    }

    async function start() {
        cancelledRef.current = false;
        setBusy(true);
        let parsed: any;
        try { parsed = JSON.parse(payload); } catch { parsed = {}; }

        if (stepThrough) {
            const created = await api.testRun({ payload: parsed, dryRun, stepThrough: true });
            const runData = await api.pollRun(created.id);
            if (!cancelledRef.current) {
                setRun(runData);
                if (runData.status === 'paused') {
                    const lastNodeId = runData.steps?.[runData.steps.length - 1]?.operation_key ?? null;
                    setPausedRun(runData);
                    storePausedRun(runData.id, lastNodeId);
                }
            }
            setBusy(false);
        } else {
            const created = await api.testRun({ payload: parsed, dryRun });
            if (!cancelledRef.current) setRun(created);
            await poll(created.id);
            if (!cancelledRef.current) setBusy(false);
        }
    }

    async function poll(id: string) {
        let r = await api.pollRun(id);
        if (!cancelledRef.current) setRun(r);
        while (!cancelledRef.current && r.status !== 'completed' && r.status !== 'failed' && r.status !== 'cancelled') {
            await new Promise((res) => setTimeout(res, pollMs));
            r = await api.pollRun(id);
            if (!cancelledRef.current) setRun(r);
        }
    }

    async function nextStep() {
        if (!pausedRun || !api.step) return;
        setBusy(true);
        await api.step(pausedRun.id, undefined);
        const updated = await api.pollRun(pausedRun.id);
        setRun(updated);
        if (updated.status === 'paused') {
            const lastNodeId = updated.steps?.[updated.steps.length - 1]?.operation_key ?? null;
            setPausedRun(updated);
            storePausedRun(updated.id, lastNodeId);
        } else {
            setPausedRun(null);
            storeClearPaused();
        }
        setBusy(false);
    }

    async function abortRun() {
        if (!pausedRun || !api.step) return;
        setBusy(true);
        await api.step(pausedRun.id, 'abort');
        const updated = await api.pollRun(pausedRun.id);
        setRun(updated);
        setPausedRun(null);
        storeClearPaused();
        setBusy(false);
    }

    return (
        <aside className="w-96 border-l bg-white dark:bg-gray-900 h-full p-4 overflow-y-auto">
            <div className="flex justify-between mb-3">
                <h3 className="font-semibold">Test Run</h3>
                <button onClick={handleClose}>×</button>
            </div>
            <label className="text-sm flex flex-col gap-1">
                <span>Payload</span>
                <textarea aria-label="Payload" className="border rounded px-2 py-1 font-mono" rows={6}
                    value={payload} onChange={(e) => setPayload(e.target.value)} />
            </label>
            <label className="mt-2 flex items-center gap-2 text-sm">
                <input type="checkbox" aria-label="Dry run" checked={dryRun} onChange={(e) => setDryRun(e.target.checked)} />
                Dry run
            </label>
            <label className="mt-2 flex items-center gap-2 text-sm">
                <input type="checkbox" aria-label="Step through" checked={stepThrough} onChange={(e) => setStepThrough(e.target.checked)} />
                Step through
            </label>
            <button onClick={start} disabled={busy} className="mt-3 px-3 py-1 text-sm border rounded bg-sky-600 text-white">Run</button>

            {stepThrough && pausedRun && (
                <div className="mt-3 flex gap-2">
                    <button onClick={nextStep} disabled={busy} className="px-3 py-1 text-sm border rounded bg-green-600 text-white">Next step</button>
                    <button onClick={abortRun} className="px-3 py-1 text-sm border rounded bg-red-600 text-white">Abort</button>
                </div>
            )}

            {run && (
                <div className="mt-4">
                    <div className="text-xs uppercase text-gray-500">Status</div>
                    <div className="mb-2">{run.status}</div>
                    <div className="space-y-2">
                        {(run.steps ?? []).map((s: any) => (
                            <div key={s.id} className="border rounded p-2 text-xs">
                                <div className="font-mono">{s.operation_key}</div>
                                <div>{s.status}</div>
                                {s.error_message && <div className="text-red-600">{s.error_message}</div>}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </aside>
    );
}
