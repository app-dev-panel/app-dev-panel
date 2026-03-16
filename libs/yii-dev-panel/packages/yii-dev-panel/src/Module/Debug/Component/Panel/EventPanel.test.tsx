import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {EventPanel} from './EventPanel';

const preloadedState = {'store.debug': {entry: {id: 'test-1', collectors: []}, currentPageRequestIds: []}};

const makeEvent = (
    overrides: Partial<{event: string; file: string; line: string; name: string; time: number}> = {},
) => ({
    event: '{}',
    file: '/src/Handler.php',
    line: '/src/Handler.php:15',
    name: 'App\\Event\\UserCreated',
    time: 1705319445.123456,
    ...overrides,
});

describe('EventPanel', () => {
    it('shows empty message when no events', () => {
        renderWithProviders(<EventPanel events={[]} />);
        expect(screen.getByText(/No dispatched events/)).toBeInTheDocument();
    });

    it('shows empty message when events is null', () => {
        renderWithProviders(<EventPanel events={null as any} />);
        expect(screen.getByText(/No dispatched events/)).toBeInTheDocument();
    });

    it('renders section title with count (singular)', () => {
        renderWithProviders(<EventPanel events={[makeEvent()]} />, {preloadedState});
        expect(screen.getByText('1 event')).toBeInTheDocument();
    });

    it('renders section title with count (plural)', () => {
        renderWithProviders(<EventPanel events={[makeEvent(), makeEvent({name: 'App\\Event\\OrderPlaced'})]} />, {
            preloadedState,
        });
        expect(screen.getByText('2 events')).toBeInTheDocument();
    });

    it('renders EVENT badge for each row', () => {
        renderWithProviders(<EventPanel events={[makeEvent()]} />, {preloadedState});
        expect(screen.getByText('EVENT')).toBeInTheDocument();
    });

    it('renders short class name from namespace', () => {
        renderWithProviders(<EventPanel events={[makeEvent({name: 'App\\Event\\UserCreated'})]} />, {preloadedState});
        expect(screen.getAllByText('UserCreated').length).toBeGreaterThan(0);
    });

    it('filters events by name', async () => {
        const user = userEvent.setup();
        const events = [makeEvent({name: 'App\\Event\\UserCreated'}), makeEvent({name: 'App\\Event\\OrderPlaced'})];
        renderWithProviders(<EventPanel events={events} />, {preloadedState});
        const input = screen.getByPlaceholderText('Filter events...');
        await user.type(input, 'Order');
        expect(screen.getAllByText('OrderPlaced').length).toBeGreaterThan(0);
        expect(screen.queryByText('UserCreated')).not.toBeInTheDocument();
    });

    it('filters events by file', async () => {
        const user = userEvent.setup();
        const events = [
            makeEvent({name: 'EventA', file: '/src/User.php'}),
            makeEvent({name: 'EventB', file: '/src/Order.php'}),
        ];
        renderWithProviders(<EventPanel events={events} />, {preloadedState});
        const input = screen.getByPlaceholderText('Filter events...');
        await user.type(input, 'Order.php');
        expect(screen.getAllByText('EventB').length).toBeGreaterThan(0);
        expect(screen.queryByText('EventA')).not.toBeInTheDocument();
    });

    it('expands event detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<EventPanel events={[makeEvent({name: 'App\\Event\\Click'})]} />, {preloadedState});
        const elements = screen.getAllByText('Click');
        await user.click(elements[0]);
        expect(screen.getByText('Open File')).toBeInTheDocument();
        expect(screen.getByText('App\\Event\\Click')).toBeInTheDocument();
    });

    it('shows filter input', () => {
        renderWithProviders(<EventPanel events={[makeEvent()]} />, {preloadedState});
        expect(screen.getByPlaceholderText('Filter events...')).toBeInTheDocument();
    });
});
