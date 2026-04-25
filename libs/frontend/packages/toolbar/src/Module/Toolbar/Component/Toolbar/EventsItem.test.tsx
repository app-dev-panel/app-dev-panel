import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {EventsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/EventsItem';
import {fireEvent, screen} from '@testing-library/react';
import {afterEach, describe, expect, it, vi} from 'vitest';

const makeEntry = (event?: DebugEntry['event']): DebugEntry => ({id: 'entry-42', collectors: [], event}) as DebugEntry;

describe('EventsItem URL shape', () => {
    const originalOpen = window.open;
    afterEach(() => {
        window.open = originalOpen;
        delete window.__adp_panel_url;
    });

    it('renders nothing when there are no events', () => {
        const {container} = renderWithProviders(
            <EventsItem data={makeEntry({total: 0})} iframeUrlHandler={() => {}} />,
        );
        expect(container.querySelector('.MuiChip-root')).toBeNull();
    });

    it('passes {mount}/debug?collector=...&debugEntry=... to the iframe handler on a normal click', () => {
        const handler = vi.fn();
        renderWithProviders(<EventsItem data={makeEntry({total: 3})} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByText('Events 3'));

        // Both /debug segments matter: first is the ADP mount, second is the panel-internal
        // collector route.
        expect(handler).toHaveBeenCalledWith(
            '/debug/debug?collector=AppDevPanel%5CKernel%5CCollector%5CEventCollector&debugEntry=entry-42',
        );
    });

    it('opens the same URL in a new tab on Ctrl+click and does not call the iframe handler', () => {
        const handler = vi.fn();
        const open = vi.fn();
        window.open = open;
        renderWithProviders(<EventsItem data={makeEntry({total: 3})} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByText('Events 3'), {ctrlKey: true});

        expect(handler).not.toHaveBeenCalled();
        expect(open).toHaveBeenCalledWith(
            '/debug/debug?collector=AppDevPanel%5CKernel%5CCollector%5CEventCollector&debugEntry=entry-42',
            '_blank',
            'noopener,noreferrer',
        );
    });

    it('honors a custom window.__adp_panel_url mount path', () => {
        window.__adp_panel_url = '/adp';
        const handler = vi.fn();
        renderWithProviders(<EventsItem data={makeEntry({total: 1})} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByText('Events 1'));

        expect(handler).toHaveBeenCalledWith(
            '/adp/debug?collector=AppDevPanel%5CKernel%5CCollector%5CEventCollector&debugEntry=entry-42',
        );
    });
});
