import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {emptyHandlers} from './mocks/handlers';
import {renderToolbar} from './renderToolbar';
import {worker} from './setup';

describe('Toolbar - Empty State', () => {
    it('renders FAB even with no debug entries', async () => {
        worker.use(...emptyHandlers);
        renderToolbar();

        // FAB should still be visible even without entries
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();
    });

    it('can open entries list modal when empty', async () => {
        worker.use(...emptyHandlers);
        renderToolbar();

        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();

        const listAction = page.getByLabelText('List all debug entries');
        await listAction.click();

        await expect.element(page.getByText('Select a debug entry')).toBeVisible();
    });
});
