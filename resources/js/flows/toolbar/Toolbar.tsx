import React, { useState } from 'react';

export function Toolbar({ flowName, dirty, saving, paletteOpen, fullscreen, onToggleFullscreen, onTogglePalette, onSave, onPublish, onTestRun, publishedVersion, draftSavedAt }: {
    flowName: string; dirty: boolean; saving: boolean; paletteOpen: boolean;
    fullscreen?: boolean;
    onToggleFullscreen?: () => void;
    publishedVersion?: { version: number; published_at?: string } | null;
    draftSavedAt?: string | null;
    onTogglePalette: () => void; onSave: () => void;
    onPublish: (changeSummary: string) => void;
    onTestRun: () => void;
}) {
    const [modalOpen, setModalOpen] = useState(false);
    const [changeSummary, setChangeSummary] = useState('');

    const statusPill = saving
        ? 'saving…'
        : dirty
            ? 'Draft (unsaved)'
            : draftSavedAt
                ? 'Draft saved'
                : publishedVersion
                    ? `Published v${publishedVersion.version}`
                    : '';

    function handlePublishClick() {
        setChangeSummary('');
        setModalOpen(true);
    }

    function handlePublishConfirm() {
        onPublish(changeSummary);
        setModalOpen(false);
        setChangeSummary('');
    }

    function handlePublishCancel() {
        setModalOpen(false);
        setChangeSummary('');
    }

    return (
        <>
            <div className="flex flex-wrap items-center justify-between gap-2 border-b px-3 py-2 bg-white dark:bg-gray-900 dark:border-gray-700">
                <div className="flex items-center gap-2 min-w-0">
                    <button
                        onClick={onTogglePalette}
                        title="Add node"
                        className={`flex items-center gap-1 px-2 py-1 text-sm border rounded transition-colors dark:border-gray-600 dark:text-gray-200
                        ${paletteOpen ? 'bg-sky-50 border-sky-400 text-sky-700 dark:bg-sky-900 dark:border-sky-500 dark:text-sky-300' : 'hover:bg-gray-50 dark:hover:bg-gray-800'}`}
                    >
                        <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                        </svg>
                        Add
                    </button>
                    <span className="font-semibold text-gray-800 dark:text-gray-100 truncate max-w-[40vw]">{flowName}</span>
                    {statusPill && (
                        <span className="hidden sm:inline text-xs text-gray-400 dark:text-gray-500">
                            {statusPill}
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    <button
                        aria-label={fullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
                        title={fullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
                        onClick={onToggleFullscreen}
                        className="p-1.5 text-sm border rounded dark:border-gray-600 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                        {fullscreen ? (
                            <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                                <path d="M9 9 4 4m0 5V4h5M15 9l5-5m-5 0h5v5M9 15l-5 5m5 0H4v-5M15 15l5 5m0-5v5h-5" />
                            </svg>
                        ) : (
                            <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" />
                            </svg>
                        )}
                    </button>
                    <button
                        className="px-3 py-1 text-sm border rounded dark:border-gray-600 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        onClick={onSave}
                    >
                        Save
                    </button>
                    <button
                        className="px-3 py-1 text-sm border rounded bg-sky-600 text-white hover:bg-sky-700 transition-colors"
                        onClick={handlePublishClick}
                    >
                        Publish
                    </button>
                    <button
                        className="px-3 py-1 text-sm border rounded dark:border-gray-600 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        onClick={onTestRun}
                    >
                        Test Run
                    </button>
                </div>
            </div>
            {modalOpen && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Publish flow"
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                >
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-96 max-w-full">
                        <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Publish flow</h2>
                        <textarea
                            className="w-full border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 resize-none"
                            rows={3}
                            placeholder="Change summary (optional)"
                            value={changeSummary}
                            onChange={(e) => setChangeSummary(e.target.value)}
                        />
                        <div className="flex justify-end gap-2 mt-4">
                            <button
                                className="px-3 py-1 text-sm border rounded dark:border-gray-600 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                onClick={handlePublishCancel}
                            >
                                Cancel
                            </button>
                            <button
                                className="px-3 py-1 text-sm border rounded bg-sky-600 text-white hover:bg-sky-700 transition-colors"
                                onClick={handlePublishConfirm}
                            >
                                Publish
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
