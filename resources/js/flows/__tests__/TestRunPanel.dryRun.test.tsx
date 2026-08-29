import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, waitFor } from '@testing-library/react';
import { TestRunPanel } from '../testrun/TestRunPanel';

vi.mock('../state/useFlowStore', () => {
    const setPausedRun = vi.fn();
    const clearPausedRun = vi.fn();
    return {
        useFlowStore: (selector: (s: any) => any) =>
            selector({ setPausedRun, clearPausedRun }),
    };
});

describe('TestRunPanel dry run toggle', () => {
    it('posts dry_run=false by default', async () => {
        const testRun = vi.fn().mockResolvedValue({ id: 'run-1' });
        const pollRun = vi.fn().mockResolvedValue({ id: 'run-1', status: 'completed', steps: [] });

        const { getByText } = render(<TestRunPanel api={{ testRun, pollRun } as any} onClose={() => {}} pollMs={10} />);
        fireEvent.click(getByText('Run'));

        await waitFor(() => expect(testRun).toHaveBeenCalledWith(
            expect.objectContaining({ dryRun: false }),
        ));
    });

    it('posts dry_run=true when the toggle is checked', async () => {
        const testRun = vi.fn().mockResolvedValue({ id: 'run-1' });
        const pollRun = vi.fn().mockResolvedValue({ id: 'run-1', status: 'completed', steps: [] });

        const { getByText, getByLabelText } = render(<TestRunPanel api={{ testRun, pollRun } as any} onClose={() => {}} pollMs={10} />);
        fireEvent.click(getByLabelText('Dry run'));
        fireEvent.click(getByText('Run'));

        await waitFor(() => expect(testRun).toHaveBeenCalledWith(
            expect.objectContaining({ dryRun: true }),
        ));
    });
});
