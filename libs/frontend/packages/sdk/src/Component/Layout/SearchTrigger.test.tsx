import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {SearchTrigger} from './SearchTrigger';

describe('SearchTrigger', () => {
    it('renders search text and keyboard shortcut', () => {
        renderWithProviders(<SearchTrigger />);
        expect(screen.getByText(/Search/)).toBeInTheDocument();
        expect(screen.getByText('Ctrl+K')).toBeInTheDocument();
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();
        renderWithProviders(<SearchTrigger onClick={onClick} />);
        await user.click(screen.getByText(/Search/));
        expect(onClick).toHaveBeenCalledOnce();
    });
});
