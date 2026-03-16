import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('Debug Page', () => {
    it('renders debug page with entry selector', async () => {
        renderApp('/debug');
        // Wait for debug entries to load — should show autocomplete or entries
        await expect.element(page.getByText('Debug')).toBeVisible();
    });

    it('shows REFRESH button', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('REFRESH')).toBeVisible();
    });

    it('shows LIST button', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('LIST')).toBeVisible();
    });

    it('shows REPEAT REQUEST button', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('REPEAT REQUEST')).toBeVisible();
    });

    it('shows Latest auto toggle', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Latest auto')).toBeVisible();
    });

    it('loads debug entries from mock API', async () => {
        renderApp('/debug');
        // The mock returns entries with IDs entry-001, entry-002
        // The autocomplete should eventually show the first entry
        await expect.element(page.getByText('Debug')).toBeVisible();
        // Give RTK Query time to fetch
        await new Promise((r) => setTimeout(r, 1000));
        // Collector names should appear in the sidebar
        const bodyText = document.body.textContent || '';
        expect(bodyText).toContain('Web');
    });

    it('renders collector sidebar with names', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Debug')).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        // Collector names from mock: Web, Log, Database, Event
        const bodyText = document.body.textContent || '';
        const hasCollector = bodyText.includes('Log') || bodyText.includes('Database') || bodyText.includes('Event');
        expect(hasCollector).toBe(true);
    });

    it('renders debug list page', async () => {
        renderApp('/debug/list');
        await expect.element(page.getByText('Debug')).toBeVisible();
    });

    it('renders debug object page', async () => {
        renderApp('/debug/object');
        await expect.element(page.getByText('MENU')).toBeVisible();
    });
});
