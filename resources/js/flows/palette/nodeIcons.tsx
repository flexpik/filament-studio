import React from 'react';

const cls = 'w-4 h-4 shrink-0';
const svg = (path: React.ReactNode) => (
    <svg className={cls} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">{path}</svg>
);

const ICONS: Record<string, React.ReactNode> = {
    // operations
    send_email: svg(<><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></>),
    http_request: svg(<><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" /></>),
    log_message: svg(<><rect x="5" y="3" width="14" height="18" rx="2" /><path d="M9 8h6M9 12h6M9 16h4" /></>),
    condition: svg(<><circle cx="6" cy="6" r="2" /><circle cx="6" cy="18" r="2" /><circle cx="18" cy="12" r="2" /><path d="M8 6h4a4 4 0 0 1 4 4M8 18h4a4 4 0 0 0 4-4" /></>),
    transform_payload: svg(<><path d="M4 7h11l-3-3M20 17H9l3 3" /></>),
    create_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M12 8v8M8 12h8" /></>),
    read_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M8 9h8M8 13h5" /></>),
    update_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="m8 14 3 3 5-7" /></>),
    delete_record: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M9 9l6 6M15 9l-6 6" /></>),
    trigger_flow: svg(<><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></>),
    // triggers
    manual: svg(<><path d="M7 11V6a2 2 0 0 1 4 0v5M11 8a2 2 0 0 1 4 0v3M15 9a2 2 0 0 1 4 0v6a4 4 0 0 1-4 4H9l-4-4" /></>),
    webhook: svg(<><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></>),
    collection_event: svg(<><rect x="3" y="4" width="18" height="4" rx="1" /><rect x="3" y="10" width="18" height="4" rx="1" /><rect x="3" y="16" width="18" height="4" rx="1" /></>),
    schedule: svg(<><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>),
};

const FALLBACK: Record<'trigger' | 'operation', React.ReactNode> = {
    trigger: svg(<><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></>),
    operation: svg(<><rect x="4" y="4" width="16" height="16" rx="2" /></>),
};

export function iconFor(type: 'trigger' | 'operation', key: string): React.ReactNode {
    return ICONS[key] ?? FALLBACK[type];
}
