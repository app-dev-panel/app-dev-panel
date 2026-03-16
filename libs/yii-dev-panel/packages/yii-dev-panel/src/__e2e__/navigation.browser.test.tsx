import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('Navigation', () => {
    it('renders the homepage with MENU link', async () => {
        renderApp('/');
        await expect.element(page.getByText('MENU')).toBeVisible();
    });

    it('renders MUI components on homepage', async () => {
        renderApp('/');
        await expect.element(page.getByText('MENU')).toBeVisible();
        const muiElements = document.querySelectorAll('[class*="Mui"]');
        expect(muiElements.length).toBeGreaterThan(0);
    });

    it('renders the backend URL input on homepage', async () => {
        renderApp('/');
        await expect.element(page.getByText('MENU')).toBeVisible();
        const inputs = document.querySelectorAll('input');
        expect(inputs.length).toBeGreaterThan(0);
    });

    it('navigates to debug page', async () => {
        renderApp('/debug');
        await expect.element(page.getByText('Debug')).toBeVisible();
    });

    it('navigates to inspector config page', async () => {
        renderApp('/inspector/config');
        await expect.element(page.getByText('Config')).toBeVisible();
    });

    it('navigates to inspector routes page', async () => {
        renderApp('/inspector/routes');
        await expect.element(page.getByText('MENU')).toBeVisible();
        expect(window.location.pathname).toBe('/inspector/routes');
    });

    it('shows 404 for unknown routes', async () => {
        renderApp('/nonexistent-page-12345');
        await expect.element(page.getByText('MENU')).toBeVisible();
    });
});
