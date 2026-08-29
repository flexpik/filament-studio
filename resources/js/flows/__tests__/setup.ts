import '@testing-library/jest-dom/vitest';

// @xyflow/react uses ResizeObserver internally; provide a no-op mock in jsdom.
if (typeof ResizeObserver === 'undefined') {
    global.ResizeObserver = class ResizeObserver {
        observe() {}
        unobserve() {}
        disconnect() {}
    };
}
