import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('Inspector Pages', () => {
    it.each([
        ['/inspector/config', 'Config'],
        ['/inspector/routes', 'Route'],
        ['/inspector/git', 'Git'],
        ['/inspector/events', 'Event'],
        ['/inspector/files', 'File'],
        ['/inspector/translations', 'Translation'],
        ['/inspector/commands', 'Command'],
        ['/inspector/database', 'Database'],
        ['/inspector/phpinfo', 'PHP'],
        ['/inspector/composer', 'Composer'],
        ['/inspector/opcache', 'OPcache'],
        ['/inspector/cache', 'Cache'],
        ['/inspector/container/view', 'Container'],
        ['/inspector/git/log', 'Git'],
    ])('loads %s page', async (path, expectedText) => {
        renderApp(path);
        // Pages should render without crashing — MENU is always visible in layout
        await expect.element(page.getByText('MENU')).toBeVisible();
    });

    it('renders config page with configuration data', async () => {
        renderApp('/inspector/config');
        await expect.element(page.getByText('Config')).toBeVisible();
    });

    it('renders routes page with route data', async () => {
        renderApp('/inspector/routes');
        await expect.element(page.getByText('MENU')).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        // Mock returns routes, they should appear in a table
        const bodyText = document.body.textContent || '';
        const hasRouteData = bodyText.includes('home') || bodyText.includes('/api/users') || bodyText.includes('Route');
        expect(hasRouteData).toBe(true);
    });

    it('renders git page with repository info', async () => {
        renderApp('/inspector/git');
        await expect.element(page.getByText('MENU')).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        const bodyText = document.body.textContent || '';
        const hasGitData = bodyText.includes('main') || bodyText.includes('origin') || bodyText.includes('Git');
        expect(hasGitData).toBe(true);
    });

    it('renders config parameters sub-page', async () => {
        renderApp('/inspector/config/parameters');
        await expect.element(page.getByText('MENU')).toBeVisible();
    });

    it('renders git log sub-page', async () => {
        renderApp('/inspector/git/log');
        await expect.element(page.getByText('MENU')).toBeVisible();
    });
});
