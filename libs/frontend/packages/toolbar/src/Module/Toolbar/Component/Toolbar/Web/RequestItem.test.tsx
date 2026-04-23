import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {RequestItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Web/RequestItem';
import {fireEvent, screen} from '@testing-library/react';
import {afterEach, describe, expect, it, vi} from 'vitest';

const makeEntry = (): DebugEntry =>
    ({
        id: 'req-77',
        collectors: [],
        request: {method: 'GET', path: '/users'},
        response: {statusCode: 200},
        router: {},
    }) as unknown as DebugEntry;

describe('RequestItem Ctrl/Cmd-click URL', () => {
    const originalOpen = window.open;
    afterEach(() => {
        window.open = originalOpen;
        delete window.__adp_panel_url;
    });

    it('opens the debug entry overview at {mount}/debug?debugEntry=<id> in a new tab on Ctrl+click', () => {
        const open = vi.fn();
        window.open = open;
        renderWithProviders(<RequestItem data={makeEntry()} />);

        fireEvent.click(screen.getByText(/GET \/users 200/), {ctrlKey: true});

        expect(open).toHaveBeenCalledWith('/debug/debug?debugEntry=req-77', '_blank', 'noopener,noreferrer');
    });

    it('honors a custom window.__adp_panel_url mount path', () => {
        window.__adp_panel_url = '/adp';
        const open = vi.fn();
        window.open = open;
        renderWithProviders(<RequestItem data={makeEntry()} />);

        fireEvent.click(screen.getByText(/GET \/users 200/), {metaKey: true});

        expect(open).toHaveBeenCalledWith('/adp/debug?debugEntry=req-77', '_blank', 'noopener,noreferrer');
    });

    it('does not call window.open on a plain click (opens the menu instead)', () => {
        const open = vi.fn();
        window.open = open;
        renderWithProviders(<RequestItem data={makeEntry()} />);

        fireEvent.click(screen.getByText(/GET \/users 200/));

        expect(open).not.toHaveBeenCalled();
    });
});
