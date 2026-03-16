import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {CollectorSidebar} from './CollectorSidebar';

const sampleItems = [
    {key: 'request', icon: 'http', label: 'Request', badge: 1},
    {key: 'log', icon: 'description', label: 'Log', badge: 15},
    {key: 'exception', icon: 'warning', label: 'Exception', badge: 2, badgeVariant: 'error' as const},
];

describe('CollectorSidebar', () => {
    it('renders all items', () => {
        renderWithProviders(<CollectorSidebar items={sampleItems} onItemClick={() => {}} />);
        expect(screen.getByText('Request')).toBeInTheDocument();
        expect(screen.getByText('Log')).toBeInTheDocument();
        expect(screen.getByText('Exception')).toBeInTheDocument();
    });

    it('renders badges', () => {
        renderWithProviders(<CollectorSidebar items={sampleItems} onItemClick={() => {}} />);
        expect(screen.getByText('15')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders overview item when onOverviewClick provided', () => {
        renderWithProviders(<CollectorSidebar items={sampleItems} onItemClick={() => {}} onOverviewClick={() => {}} />);
        expect(screen.getByText('Overview')).toBeInTheDocument();
    });

    it('does not render overview when onOverviewClick not provided', () => {
        renderWithProviders(<CollectorSidebar items={sampleItems} onItemClick={() => {}} />);
        expect(screen.queryByText('Overview')).not.toBeInTheDocument();
    });

    it('calls onItemClick with correct key', async () => {
        const user = userEvent.setup();
        const onItemClick = vi.fn();
        renderWithProviders(<CollectorSidebar items={sampleItems} onItemClick={onItemClick} />);
        await user.click(screen.getByText('Log'));
        expect(onItemClick).toHaveBeenCalledWith('log');
    });

    it('calls onOverviewClick when overview clicked', async () => {
        const user = userEvent.setup();
        const onOverviewClick = vi.fn();
        renderWithProviders(
            <CollectorSidebar items={sampleItems} onItemClick={() => {}} onOverviewClick={onOverviewClick} />,
        );
        await user.click(screen.getByText('Overview'));
        expect(onOverviewClick).toHaveBeenCalledOnce();
    });

    it('renders empty when items is empty', () => {
        const {container} = renderWithProviders(<CollectorSidebar items={[]} onItemClick={() => {}} />);
        expect(container.querySelector('[class*="MuiPaper"]')).toBeTruthy();
    });
});
