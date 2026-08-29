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

describe('TestRunPanel step-through mode', () => {
    it('calls POST step on "Next step"', async () => {
        const testRun = vi.fn().mockResolvedValue({ id: 'run-1' });
        const pollRun = vi.fn()
            .mockResolvedValueOnce({ id: 'run-1', status: 'paused', steps: [] })
            .mockResolvedValueOnce({ id: 'run-1', status: 'paused', steps: [] });
        const step = vi.fn().mockResolvedValue({});

        const { getByText, getByLabelText } = render(
            <TestRunPanel api={{ testRun, pollRun, step } as any} onClose={() => {}} pollMs={10} />
        );

        fireEvent.click(getByLabelText('Step through'));
        fireEvent.click(getByText('Run'));

        await waitFor(() => expect(getByText('Next step')).toBeInTheDocument());

        fireEvent.click(getByText('Next step'));
        await waitFor(() => expect(step).toHaveBeenCalledWith('run-1', undefined));
    });

    it('aborts with action=abort', async () => {
        const testRun = vi.fn().mockResolvedValue({ id: 'run-1' });
        const pollRun = vi.fn()
            .mockResolvedValueOnce({ id: 'run-1', status: 'paused', steps: [] })
            .mockResolvedValueOnce({ id: 'run-1', status: 'aborted', steps: [] });
        const step = vi.fn().mockResolvedValue({});

        const { getByText, getByLabelText } = render(
            <TestRunPanel api={{ testRun, pollRun, step } as any} onClose={() => {}} pollMs={10} />
        );

        fireEvent.click(getByLabelText('Step through'));
        fireEvent.click(getByText('Run'));

        await waitFor(() => expect(getByText('Abort')).toBeInTheDocument());

        fireEvent.click(getByText('Abort'));
        await waitFor(() => expect(step).toHaveBeenCalledWith('run-1', 'abort'));
    });
});
