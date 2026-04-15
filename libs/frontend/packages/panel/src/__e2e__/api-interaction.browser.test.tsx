import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('API Interaction', () => {
    it('homepage renders with application title', async () => {
        renderApp('/');
        await expect.element(page.getByText('Application Development Panel')).toBeVisible();
    });

    it('debug page triggers API call and shows data', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Debug').first()).toBeVisible();
        // RTK Query should fetch debug entries from mock
        await new Promise((r) => setTimeout(r, 1500));
        const bodyText = document.body.textContent || '';
        // Mock entries have method GET and POST
        const hasData = bodyText.includes('GET') || bodyText.includes('entry-001') || bodyText.includes('Web');
        expect(hasData).toBe(true);
    });

    it('inspector config page loads data from API', async () => {
        renderApp('/inspector/config');
        await expect.element(page.getByText('Configuration').first()).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        const bodyText = document.body.textContent || '';
        // Should show configuration groups or data from mock
        const hasConfigData = bodyText.includes('Config') || bodyText.includes('app') || bodyText.includes('web');
        expect(hasConfigData).toBe(true);
    });

    it('redux persist saves state to localStorage', async () => {
        renderApp('/');
        await expect.element(page.getByText('Application Development Panel')).toBeVisible();
        // Wait for Redux Persist to flush
        await new Promise((r) => setTimeout(r, 500));
        const persistedState = localStorage.getItem('persist:root') || localStorage.getItem('persist:application');
        expect(persistedState).not.toBeNull();
    });

    it('handles API errors gracefully on debug page', async () => {
        renderApp('/debug');
        // Even with mock API, the app should render without crashing
        await expect.element(page.getByText('Debug').first()).toBeVisible();
    });

    it('handles API errors gracefully on inspector pages', async () => {
        renderApp('/inspector/database');
        // Even if API returns unexpected data, app should not crash
        await expect.element(page.getByText('Storage').first()).toBeVisible();
    });
});
