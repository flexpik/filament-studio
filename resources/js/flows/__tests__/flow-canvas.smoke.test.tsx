import { describe, it, expect, vi } from 'vitest';
import { render } from '@testing-library/react';
import { FlowCanvasApp } from '../flow-canvas';
import * as apiMod from '../api/useFlowApi';

describe('FlowCanvasApp', () => {
    it('renders the canvas root container', () => {
        vi.spyOn(apiMod, 'createFlowApi').mockReturnValue({
            loadGraph: vi.fn().mockResolvedValue({ draft_graph: null, draft_updated_at: null, published_version: null }),
            saveGraph: vi.fn().mockResolvedValue(undefined),
            publish: vi.fn(),
            loadMeta: vi.fn().mockResolvedValue({ triggers: [], operations: [], collections: [] }),
            testRun: vi.fn(),
            pollRun: vi.fn(),
        });

        const { container } = render(<FlowCanvasApp flowId="test-flow" apiBase="/api/studio" apiKey="" flowName="Test" />);
        // The full app renders a flex column wrapper containing the Toolbar and the canvas area.
        expect(container.firstChild).toBeTruthy();
    });
});
