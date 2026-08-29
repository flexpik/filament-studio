import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/react';
import { ObjectBuilder } from '../inspector/ObjectBuilder';

describe('ObjectBuilder', () => {
    it('renders existing object entries', () => {
        const { getByDisplayValue } = render(<ObjectBuilder value={{ title: 'Hello', count: 42 }} onChange={() => {}} />);
        expect(getByDisplayValue('title')).toBeInTheDocument();
        expect(getByDisplayValue('Hello')).toBeInTheDocument();
        expect(getByDisplayValue('42')).toBeInTheDocument();
    });

    it('adds a new field', () => {
        const onChange = vi.fn();
        const { getByText, getAllByPlaceholderText } = render(<ObjectBuilder value={{}} onChange={onChange} />);
        fireEvent.click(getByText('+ add field'));
        const keyInputs = getAllByPlaceholderText('key');
        fireEvent.change(keyInputs[keyInputs.length - 1], { target: { value: 'name' } });
        expect(onChange).toHaveBeenLastCalledWith({ name: '' });
    });

    it('edits a string value', () => {
        const onChange = vi.fn();
        const { getByDisplayValue } = render(<ObjectBuilder value={{ title: 'a' }} onChange={onChange} />);
        fireEvent.change(getByDisplayValue('a'), { target: { value: 'b' } });
        expect(onChange).toHaveBeenLastCalledWith({ title: 'b' });
    });

    it('switches a value type to number and coerces', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(<ObjectBuilder value={{ n: 'x' }} onChange={onChange} />);
        fireEvent.change(getByLabelText('type for n'), { target: { value: 'number' } });
        expect(onChange).toHaveBeenLastCalledWith({ n: 0 });
    });

    it('switches a value type to boolean', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(<ObjectBuilder value={{ flag: '' }} onChange={onChange} />);
        fireEvent.change(getByLabelText('type for flag'), { target: { value: 'boolean' } });
        expect(onChange).toHaveBeenLastCalledWith({ flag: false });
    });

    it('switches to nested object and edits a nested field', () => {
        const onChange = vi.fn();
        const { getByLabelText, getAllByText, getAllByPlaceholderText } = render(<ObjectBuilder value={{ meta: '' }} onChange={onChange} />);
        fireEvent.change(getByLabelText('type for meta'), { target: { value: 'object' } });
        expect(onChange).toHaveBeenLastCalledWith({ meta: {} });
        // After switching to object, two "+ add field" buttons exist: the nested one
        // (rendered inside the meta row) comes first in DOM order, the outer one last.
        const addButtons = getAllByText('+ add field');
        fireEvent.click(addButtons[0]);
        const keyInputs = getAllByPlaceholderText('key');
        fireEvent.change(keyInputs[keyInputs.length - 1], { target: { value: 'k' } });
        expect(onChange).toHaveBeenLastCalledWith({ meta: { k: '' } });
    });

    it('deletes a field', () => {
        const onChange = vi.fn();
        const { getByLabelText } = render(<ObjectBuilder value={{ a: '1', b: '2' }} onChange={onChange} />);
        fireEvent.click(getByLabelText('remove a'));
        expect(onChange).toHaveBeenLastCalledWith({ b: '2' });
    });

    it('edits array items', () => {
        const onChange = vi.fn();
        const { getByText, getAllByPlaceholderText } = render(<ObjectBuilder value={[]} onChange={onChange} />);
        fireEvent.click(getByText('+ add item'));
        const valInputs = getAllByPlaceholderText('value');
        fireEvent.change(valInputs[valInputs.length - 1], { target: { value: 'x' } });
        expect(onChange).toHaveBeenLastCalledWith(['x']);
    });
});
