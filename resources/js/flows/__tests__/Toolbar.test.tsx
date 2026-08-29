import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { Toolbar } from '../toolbar/Toolbar';

describe('Toolbar', () => {
    it('shows the flow name and dirty indicator', () => {
        const { getByText } = render(<Toolbar flowName="My Flow" dirty={true} saving={false} paletteOpen={false} onTogglePalette={() => {}} onSave={() => {}} onPublish={() => {}} onTestRun={() => {}} />);
        expect(getByText('My Flow')).toBeInTheDocument();
        expect(getByText(/Draft \(unsaved\)/i)).toBeInTheDocument();
    });

    it('renders a fullscreen toggle and calls onToggleFullscreen when clicked', () => {
        const onToggleFullscreen = vi.fn();
        const { getByLabelText } = render(<Toolbar flowName="X" dirty={false} saving={false} paletteOpen={false} fullscreen={false} onToggleFullscreen={onToggleFullscreen} onTogglePalette={() => {}} onSave={() => {}} onPublish={() => {}} onTestRun={() => {}} />);
        fireEvent.click(getByLabelText('Enter fullscreen'));
        expect(onToggleFullscreen).toHaveBeenCalled();
    });

    it('shows the exit-fullscreen label when fullscreen is active', () => {
        const { getByLabelText } = render(<Toolbar flowName="X" dirty={false} saving={false} paletteOpen={false} fullscreen={true} onToggleFullscreen={() => {}} onTogglePalette={() => {}} onSave={() => {}} onPublish={() => {}} onTestRun={() => {}} />);
        expect(getByLabelText('Exit fullscreen')).toBeInTheDocument();
    });

    it('Publish button opens modal and calls onPublish with summary', () => {
        const onPublish = vi.fn();
        const { getAllByText, getByRole, getByPlaceholderText } = render(
            <Toolbar flowName="X" dirty={false} saving={false} paletteOpen={false}
                onTogglePalette={() => {}} onSave={() => {}} onPublish={onPublish} onTestRun={() => {}} />
        );
        // click the toolbar Publish button (first one)
        fireEvent.click(getAllByText('Publish')[0]);
        expect(getByRole('dialog')).toBeInTheDocument();
        fireEvent.change(getByPlaceholderText(/Change summary/i), { target: { value: 'my summary' } });
        // click the modal Publish button (now second)
        fireEvent.click(getAllByText('Publish')[1]);
        expect(onPublish).toHaveBeenCalledWith('my summary');
    });

    it('Publish modal Cancel closes without calling onPublish', () => {
        const onPublish = vi.fn();
        const { getByText, queryByRole } = render(
            <Toolbar flowName="X" dirty={false} saving={false} paletteOpen={false}
                onTogglePalette={() => {}} onSave={() => {}} onPublish={onPublish} onTestRun={() => {}} />
        );
        fireEvent.click(getByText('Publish'));
        fireEvent.click(getByText('Cancel'));
        expect(onPublish).not.toHaveBeenCalled();
        expect(queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('shows Published vN pill when publishedVersion is set and no draft', () => {
        const { getByText } = render(
            <Toolbar flowName="F" dirty={false} saving={false} paletteOpen={false}
                publishedVersion={{ version: 3, published_at: '' }}
                draftSavedAt={null}
                onTogglePalette={() => {}} onSave={() => {}} onPublish={() => {}} onTestRun={() => {}} />
        );
        expect(getByText(/Published v3/i)).toBeInTheDocument();
    });

    it('shows Draft (unsaved) pill when dirty', () => {
        const { getByText } = render(
            <Toolbar flowName="F" dirty={true} saving={false} paletteOpen={false}
                onTogglePalette={() => {}} onSave={() => {}} onPublish={() => {}} onTestRun={() => {}} />
        );
        expect(getByText(/Draft \(unsaved\)/i)).toBeInTheDocument();
    });

    it('shows Draft saved pill when draftSavedAt set and not dirty', () => {
        const { getByText } = render(
            <Toolbar flowName="F" dirty={false} saving={false} paletteOpen={false}
                draftSavedAt="2026-05-11T00:00:00Z"
                publishedVersion={null}
                onTogglePalette={() => {}} onSave={() => {}} onPublish={() => {}} onTestRun={() => {}} />
        );
        expect(getByText(/Draft saved/i)).toBeInTheDocument();
    });
});
