import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderToolbar} from './renderToolbar';
import './setup';

describe('Toolbar', () => {
    it('renders the FAB button', async () => {
        renderToolbar();
        await expect.element(page.getByRole('button', {name: /speed/i}).first()).toBeVisible();
    });

    it('renders request info when debug entries exist', async () => {
        renderToolbar();
        // Wait for data to load, then click FAB to open toolbar
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();
        await fab.click();

        // Should show the request method and path from mock data
        await expect.element(page.getByText('GET')).toBeVisible();
    });

    it('shows speed dial actions on hover', async () => {
        renderToolbar();
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();

        // Open in new window action should exist
        await expect.element(page.getByLabelText('Open debug in a new window')).toBeInTheDocument();
    });

    it('opens debug entries list modal', async () => {
        renderToolbar();
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();

        // Click the list action
        const listAction = page.getByLabelText('List all debug entries');
        await listAction.click();

        // Modal should appear with entries
        await expect.element(page.getByText('Select a debug entry')).toBeVisible();
    });

    it('displays request time metric', async () => {
        renderToolbar();
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();
        await fab.click();

        // Should display processing time (42ms = 0.042s)
        await expect.element(page.getByText(/0\.04/)).toBeVisible();
    });

    it('displays log count badge', async () => {
        renderToolbar();
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();
        await fab.click();

        // Should show log count from mock (5)
        await expect.element(page.getByText('5')).toBeVisible();
    });

    it('displays event count badge', async () => {
        renderToolbar();
        const fab = page.getByRole('button').first();
        await expect.element(fab).toBeVisible();
        await fab.click();

        // Should show event count from mock (12)
        await expect.element(page.getByText('12')).toBeVisible();
    });
});
