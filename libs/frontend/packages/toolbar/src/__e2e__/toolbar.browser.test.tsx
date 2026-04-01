import {fireEvent, screen, waitFor} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderToolbar} from './renderToolbar';
import './setup';

const expandToolbar = async () => {
    await waitFor(() => {
        // Wait for either pill or expanded toolbar to appear
        const pill = screen.queryByLabelText('Open debug toolbar');
        const toolbar = screen.queryByLabelText('Collapse toolbar');
        expect(pill || toolbar).not.toBeNull();
    });
    const pill = screen.queryByLabelText('Open debug toolbar');
    if (pill) {
        fireEvent.click(pill);
    }
    await waitFor(() => {
        expect(screen.getByLabelText('Collapse toolbar')).toBeInTheDocument();
    });
};

describe('Toolbar', () => {
    it('shows request info chip when expanded', async () => {
        renderToolbar();
        await expandToolbar();

        await waitFor(() => {
            expect(screen.getByText('GET /api/test 200')).toBeInTheDocument();
        });
    });

    it('shows action buttons when expanded', async () => {
        renderToolbar();
        await expandToolbar();

        expect(screen.getByLabelText('List debug entries')).toBeInTheDocument();
        expect(screen.getByLabelText('Open debug panel')).toBeInTheDocument();
    });

    it('opens debug entries list modal', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('List debug entries'));
        await waitFor(() => {
            expect(screen.getByText('Select a debug entry')).toBeInTheDocument();
        });
    });

    it('displays request time metric', async () => {
        renderToolbar();
        await expandToolbar();

        await waitFor(() => {
            expect(screen.getByText(/0\.042/)).toBeInTheDocument();
        });
    });

    it('can collapse toolbar', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('Collapse toolbar'));
        await waitFor(() => {
            expect(screen.getByLabelText('Open debug toolbar')).toBeInTheDocument();
        });
    });
});
