import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('Debug Page', () => {
    it('renders debug page with sidebar', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Debug').first()).toBeVisible();
    });

    it('renders top bar', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('App Dev Panel')).toBeVisible();
    });

    it('shows inspector in sidebar', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Inspector').first()).toBeVisible();
    });

    it('loads debug entries from mock API', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Debug').first()).toBeVisible();
        // Give RTK Query time to fetch
        await new Promise((r) => setTimeout(r, 1000));
        // Collector names should appear in the sidebar
        const bodyText = document.body.textContent || '';
        expect(bodyText).toContain('Web');
    });

    it('renders collector sidebar with names', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Debug').first()).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        // Collector names from mock: Web, Log, Database, Event
        const bodyText = document.body.textContent || '';
        const hasCollector = bodyText.includes('Log') || bodyText.includes('Database') || bodyText.includes('Event');
        expect(hasCollector).toBe(true);
    });

    it('renders debug list page', async () => {
        renderApp('/debug/list');
        await expect.element(page.getByText('Debug').first()).toBeVisible();
    });

    it('renders debug object page without crashing', async () => {
        renderApp('/debug/object');
        await expect.element(page.getByText('App Dev Panel')).toBeVisible();
    });
});
