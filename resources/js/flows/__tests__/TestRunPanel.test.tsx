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

describe('TestRunPanel', () => {
    it('starts a run on submit and shows steps as they complete', async () => {
        const testRun = vi.fn().mockResolvedValue({ id: 'run-1' });
        const pollRun = vi.fn()
            .mockResolvedValueOnce({ id: 'run-1', status: 'running', steps: [{ id: 's1', operation_key: 'a', status: 'completed' }] })
            .mockResolvedValueOnce({ id: 'run-1', status: 'completed', steps: [{ id: 's1', operation_key: 'a', status: 'completed' }] });

        const { getByText, getByLabelText } = render(<TestRunPanel api={{ testRun, pollRun } as any} onClose={() => {}} pollMs={10} />);

        fireEvent.change(getByLabelText('Payload'), { target: { value: '{}' } });
        fireEvent.click(getByText('Run'));

        await waitFor(() => expect(testRun).toHaveBeenCalledWith(expect.objectContaining({ payload: {} })), { timeout: 1000 });
        await waitFor(() => expect(getByText(/completed/i)).toBeInTheDocument(), { timeout: 1000 });
    });
});
