import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {MemoryItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/MemoryItem';
import {fireEvent, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';

const makeEntry = (extra: Partial<DebugEntry>): DebugEntry =>
    ({id: 'entry-1', collectors: [], ...extra}) as unknown as DebugEntry;

describe('MemoryItem', () => {
    it('renders nothing when the entry has neither web nor console timing', () => {
        const {container} = renderWithProviders(<MemoryItem data={makeEntry({})} iframeUrlHandler={() => {}} />);
        expect(container.querySelector('.MuiChip-root')).toBeNull();
    });

    it('renders nothing when timing lacks memory data', () => {
        const {container} = renderWithProviders(
            <MemoryItem
                data={makeEntry({web: {request: {processingTime: 1}}} as unknown as Partial<DebugEntry>)}
                iframeUrlHandler={() => {}}
            />,
        );
        expect(container.querySelector('.MuiChip-root')).toBeNull();
    });

    it('renders formatted peak memory and opens the web app-info collector', () => {
        const handler = vi.fn();
        renderWithProviders(
            <MemoryItem
                data={makeEntry({
                    web: {request: {processingTime: 1}, memory: {peakUsage: 2 * 1024 * 1024}},
                    request: {method: 'GET', path: '/'},
                } as unknown as Partial<DebugEntry>)}
                iframeUrlHandler={handler}
            />,
        );

        fireEvent.click(screen.getByText('2 MB'));

        expect(handler).toHaveBeenCalledWith(
            `/debug/debug?collector=${encodeURIComponent(CollectorsMap.WebAppInfoCollector)}&debugEntry=entry-1`,
        );
    });
});
