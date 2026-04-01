import {fireEvent, screen, waitFor} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {emptyHandlers} from './mocks/handlers';
import {renderToolbar} from './renderToolbar';
import {worker} from './setup';

describe('Toolbar - Empty State', () => {
    it('renders collapsed pill even with no debug entries', async () => {
        worker.use(...emptyHandlers);
        renderToolbar();

        await waitFor(() => {
            expect(screen.getByLabelText('Open debug toolbar')).toBeInTheDocument();
        });
    });

    it('expands toolbar and shows action buttons when empty', async () => {
        worker.use(...emptyHandlers);
        renderToolbar();

        await waitFor(() => {
            expect(screen.getByLabelText('Open debug toolbar')).toBeInTheDocument();
        });
        fireEvent.click(screen.getByLabelText('Open debug toolbar'));

        await waitFor(() => {
            expect(screen.getByLabelText('List debug entries')).toBeInTheDocument();
            expect(screen.getByLabelText('Open debug panel')).toBeInTheDocument();
        });
    });
});
