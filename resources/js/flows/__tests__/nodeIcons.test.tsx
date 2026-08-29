import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import { iconFor } from '../palette/nodeIcons';

describe('iconFor', () => {
    it('returns an svg element for a known operation key', () => {
        const { container } = render(<>{iconFor('operation', 'send_email')}</>);
        expect(container.querySelector('svg')).toBeTruthy();
    });

    it('returns an svg element for an unknown key (fallback)', () => {
        const { container } = render(<>{iconFor('trigger', 'totally_unknown_key')}</>);
        expect(container.querySelector('svg')).toBeTruthy();
    });
});
