import { describe, it, expect, vi } from 'vitest';
import { fireEvent, render } from '@testing-library/react';
import { FieldRenderer } from '../inspector/FieldRenderer';

describe('FieldRenderer', () => {
    it('renders a string input and reports onChange', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(
            <FieldRenderer name="to" schema={{ type: 'string', label: 'To' }} value="" onChange={onChange} />,
        );
        fireEvent.change(getByLabelText('To'), { target: { value: 'a@b.com' } });
        expect(onChange).toHaveBeenCalledWith('a@b.com');
    });

    it('renders an enum select', () => {
        const { getByLabelText } = render(
            <FieldRenderer name="level" schema={{ type: 'enum', label: 'Level', options: ['debug', 'info'] }} value="info" onChange={() => {}} />,
        );
        expect((getByLabelText('Level') as HTMLSelectElement).value).toBe('info');
    });

    it('applies dark-mode classes to the enum select for theme compatibility', () => {
        const { getByLabelText } = render(
            <FieldRenderer name="level" schema={{ type: 'enum', label: 'Level', options: ['debug', 'info'] }} value="info" onChange={() => {}} />,
        );
        const select = getByLabelText('Level') as HTMLSelectElement;
        expect(select.className).toContain('dark:bg-gray-800');
        expect(select.className).toContain('dark:text-gray-100');
    });

    it('renders a boolean toggle', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(
            <FieldRenderer name="enabled" schema={{ type: 'boolean', label: 'Enabled' }} value={false} onChange={onChange} />,
        );
        fireEvent.click(getByLabelText('Enabled'));
        expect(onChange).toHaveBeenCalledWith(true);
    });
});

describe('FieldRenderer json builder', () => {
    it('renders the object builder by default and edits produce an object', () => {
        const onChange = vi.fn();
        const { getByText, getAllByPlaceholderText } = render(
            <FieldRenderer name="data" schema={{ type: 'json', label: 'Data' }} value={{}} onChange={onChange} />
        );
        fireEvent.click(getByText('+ add field'));
        const keyInputs = getAllByPlaceholderText('key');
        fireEvent.change(keyInputs[keyInputs.length - 1], { target: { value: 'title' } });
        expect(onChange).toHaveBeenLastCalledWith({ title: '' });
    });

    it('toggles to raw JSON mode and parses input', () => {
        const onChange = vi.fn();
        const { getByText, getByLabelText } = render(
            <FieldRenderer name="data" schema={{ type: 'json', label: 'Data' }} value={{}} onChange={onChange} />
        );
        fireEvent.click(getByText('{} JSON'));
        fireEvent.change(getByLabelText('Data (raw JSON)'), { target: { value: '{"a":1}' } });
        expect(onChange).toHaveBeenLastCalledWith({ a: 1 });
    });

    it('passes through the raw string when JSON is invalid in JSON mode', () => {
        const onChange = vi.fn();
        const { getByText, getByLabelText } = render(
            <FieldRenderer name="data" schema={{ type: 'json', label: 'Data' }} value={{}} onChange={onChange} />
        );
        fireEvent.click(getByText('{} JSON'));
        fireEvent.change(getByLabelText('Data (raw JSON)'), { target: { value: '{not valid' } });
        expect(onChange).toHaveBeenLastCalledWith('{not valid');
    });
});
