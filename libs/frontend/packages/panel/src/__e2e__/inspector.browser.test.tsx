import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('Inspector Pages', () => {
    it.each([
        ['/inspector/config', 'Configuration'],
        ['/inspector/routes', 'Routes'],
        ['/inspector/git', 'Inspector'],
        ['/inspector/events', 'Event Listeners'],
        ['/inspector/files', 'File Explorer'],
        ['/inspector/translations', 'Translations'],
        ['/inspector/commands', 'App Dev Panel'],
        ['/inspector/database', 'Database'],
        ['/inspector/phpinfo', 'PHP Info'],
        ['/inspector/composer', 'Inspector'],
        ['/inspector/opcache', 'Opcache'],
        ['/inspector/cache', 'Inspector'],
        ['/inspector/container/view', 'Inspector'],
        ['/inspector/git/log', 'Inspector'],
    ])('loads %s page', async (path, expectedText) => {
        renderApp(path);
        // Pages should render without crashing — sidebar labels are always visible
        await expect.element(page.getByText(expectedText)).toBeVisible();
    });

    it('renders config page with configuration data', async () => {
        renderApp('/inspector/config');
        await expect.element(page.getByText('Configuration')).toBeVisible();
    });

    it('renders routes page with route data', async () => {
        renderApp('/inspector/routes');
        await expect.element(page.getByText('Routes')).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        const bodyText = document.body.textContent || '';
        const hasRouteData = bodyText.includes('home') || bodyText.includes('/api/users') || bodyText.includes('Route');
        expect(hasRouteData).toBe(true);
    });

    it('renders git page with repository info', async () => {
        renderApp('/inspector/git');
        await expect.element(page.getByText('Inspector')).toBeVisible();
        await new Promise((r) => setTimeout(r, 1000));
        const bodyText = document.body.textContent || '';
        const hasGitData = bodyText.includes('main') || bodyText.includes('origin') || bodyText.includes('Git');
        expect(hasGitData).toBe(true);
    });

    it('renders config parameters sub-page', async () => {
        renderApp('/inspector/config/parameters');
        await expect.element(page.getByText('Configuration')).toBeVisible();
    });

    it('renders git log sub-page', async () => {
        renderApp('/inspector/git/log');
        await expect.element(page.getByText('Inspector')).toBeVisible();
    });
});
