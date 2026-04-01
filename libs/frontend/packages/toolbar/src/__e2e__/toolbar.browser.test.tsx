import {fireEvent, screen, waitFor} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderToolbar} from './renderToolbar';
import './setup';

const expandToolbar = async () => {
    await waitFor(
        () => {
            const pill = screen.queryByLabelText('Open debug toolbar');
            const toolbar = screen.queryByLabelText('Collapse toolbar');
            expect(pill || toolbar).not.toBeNull();
        },
        {timeout: 5000},
    );
    const pill = screen.queryByLabelText('Open debug toolbar');
    if (pill) {
        fireEvent.click(pill);
    }
    await waitFor(
        () => {
            expect(screen.getByLabelText('Collapse toolbar')).toBeInTheDocument();
        },
        {timeout: 3000},
    );
};

describe('Toolbar', () => {
    it('shows request info and actions when expanded', async () => {
        renderToolbar();
        await expandToolbar();

        expect(screen.getByText('GET /api/test 200')).toBeInTheDocument();
        expect(screen.getByLabelText('List debug entries')).toBeInTheDocument();
        expect(screen.getByLabelText('Open debug panel')).toBeInTheDocument();
    });

    it('can collapse toolbar', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('Collapse toolbar'));
        await waitFor(
            () => {
                expect(screen.getByLabelText('Open debug toolbar')).toBeInTheDocument();
            },
            {timeout: 3000},
        );
    });
});
