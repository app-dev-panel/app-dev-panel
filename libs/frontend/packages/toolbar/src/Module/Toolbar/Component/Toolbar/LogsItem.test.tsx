import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {LogsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/LogsItem';
import {fireEvent, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';

const makeEntry = (logger: DebugEntry['logger']): DebugEntry =>
    ({id: 'entry-123', collectors: [], logger}) as DebugEntry;

describe('LogsItem', () => {
    it('renders nothing when total is 0', () => {
        const {container} = renderWithProviders(<LogsItem data={makeEntry({total: 0})} iframeUrlHandler={() => {}} />);
        expect(container.querySelector('.MuiChip-root')).toBeNull();
    });

    it('renders a single count when only one group has entries', () => {
        renderWithProviders(
            <LogsItem data={makeEntry({total: 5, byLevel: {info: 3, debug: 2}})} iframeUrlHandler={() => {}} />,
        );
        expect(screen.getByText('Logs')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.queryByText('/')).toBeNull();
    });

    it('renders info/warnings/errors in order with separators', () => {
        renderWithProviders(
            <LogsItem
                data={makeEntry({total: 10, byLevel: {info: 6, warning: 3, error: 1}})}
                iframeUrlHandler={() => {}}
            />,
        );
        expect(screen.getByText('6')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getAllByText('/')).toHaveLength(2);
    });

    it('skips groups with zero count', () => {
        renderWithProviders(
            <LogsItem data={makeEntry({total: 7, byLevel: {info: 6, error: 1}})} iframeUrlHandler={() => {}} />,
        );
        expect(screen.getByText('6')).toBeInTheDocument();
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getAllByText('/')).toHaveLength(1);
    });

    it('navigates to the filtered URL when a group count is clicked', () => {
        const handler = vi.fn();
        renderWithProviders(
            <LogsItem
                data={makeEntry({total: 9, byLevel: {error: 1, warning: 3, info: 5}})}
                iframeUrlHandler={handler}
            />,
        );

        fireEvent.click(screen.getByText('1'));
        const errClickUrl = handler.mock.calls[handler.mock.calls.length - 1][0] as string;
        // The URL must be: {mount}/debug?collector=<encoded>&debugEntry=<id>[&level=...]
        // The first `/debug` is the ADP mount path (here the default); the second `/debug`
        // is the panel-internal React Router path (the collector viewer). Losing the second
        // `/debug` would mean opening the panel home page instead of the Log collector view.
        expect(errClickUrl).toBe(
            '/debug/debug?collector=AppDevPanel%5CKernel%5CCollector%5CLogCollector' +
                '&debugEntry=entry-123&level=emergency,alert,critical,error',
        );

        fireEvent.click(screen.getByText('3'));
        const warnClickUrl = handler.mock.calls[handler.mock.calls.length - 1][0] as string;
        expect(warnClickUrl).toContain('&level=warning,notice');

        fireEvent.click(screen.getByText('5'));
        const infoClickUrl = handler.mock.calls[handler.mock.calls.length - 1][0] as string;
        expect(infoClickUrl).toContain('&level=info,debug');
    });

    it('navigates without a level filter when the chip body is clicked', () => {
        const handler = vi.fn();
        renderWithProviders(<LogsItem data={makeEntry({total: 2, byLevel: {info: 2}})} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByText('Logs'));
        expect(handler).toHaveBeenCalled();
        const url = handler.mock.calls[0][0] as string;
        expect(url).toBe('/debug/debug?collector=AppDevPanel%5CKernel%5CCollector%5CLogCollector&debugEntry=entry-123');
        expect(url).not.toContain('&level=');
    });

    it('falls back to single info count when byLevel is missing', () => {
        renderWithProviders(<LogsItem data={makeEntry({total: 4})} iframeUrlHandler={() => {}} />);
        expect(screen.getByText('4')).toBeInTheDocument();
    });
});
