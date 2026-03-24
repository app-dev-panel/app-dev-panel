import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {QueuePanel} from './QueuePanel';

const makeMessage = (
    overrides: Partial<{
        messageClass: string;
        bus: string;
        transport: string | null;
        dispatched: boolean;
        handled: boolean;
        failed: boolean;
        duration: number;
        message: any;
    }> = {},
) => ({
    messageClass: 'App\\Message\\SendEmail',
    bus: 'messenger.bus.default',
    transport: 'async',
    dispatched: true,
    handled: false,
    failed: false,
    duration: 12.5,
    message: null,
    ...overrides,
});

const emptyData = {pushes: {}, statuses: [], processingMessages: {}};

describe('QueuePanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<QueuePanel data={null as any} />);
        expect(screen.getByText(/No queue data found/)).toBeInTheDocument();
    });

    it('shows empty message when no operations', () => {
        renderWithProviders(<QueuePanel data={emptyData} />);
        expect(screen.getByText(/No queue operations found/)).toBeInTheDocument();
    });

    it('renders messages view when only messages exist', () => {
        renderWithProviders(<QueuePanel data={{...emptyData, messages: [makeMessage()]}} />);
        expect(screen.getByText('1 messages')).toBeInTheDocument();
        expect(screen.getByText('SendEmail')).toBeInTheDocument();
    });

    it('renders bus chip', () => {
        renderWithProviders(<QueuePanel data={{...emptyData, messages: [makeMessage()]}} />);
        expect(screen.getByText('messenger.bus.default')).toBeInTheDocument();
    });

    it('renders transport chip', () => {
        renderWithProviders(<QueuePanel data={{...emptyData, messages: [makeMessage({transport: 'async'})]}} />);
        expect(screen.getByText('async')).toBeInTheDocument();
    });

    it('renders DISPATCHED chip for dispatched message', () => {
        renderWithProviders(
            <QueuePanel data={{...emptyData, messages: [makeMessage({dispatched: true, handled: false})]}} />,
        );
        expect(screen.getByText('DISPATCHED')).toBeInTheDocument();
    });

    it('renders HANDLED chip for handled message', () => {
        renderWithProviders(
            <QueuePanel data={{...emptyData, messages: [makeMessage({handled: true, dispatched: true})]}} />,
        );
        expect(screen.getByText('HANDLED')).toBeInTheDocument();
    });

    it('renders FAILED chip for failed message', () => {
        renderWithProviders(<QueuePanel data={{...emptyData, messages: [makeMessage({failed: true})]}} />);
        expect(screen.getByText('FAILED')).toBeInTheDocument();
    });

    it('expands message to show full class name', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <QueuePanel data={{...emptyData, messages: [makeMessage({messageClass: 'App\\Message\\DoWork'})]}} />,
        );
        await user.click(screen.getByText('DoWork'));
        expect(screen.getByText('Full Class Name')).toBeInTheDocument();
        expect(screen.getByText('App\\Message\\DoWork')).toBeInTheDocument();
    });

    it('renders pushes tab when pushes exist', () => {
        const data = {...emptyData, pushes: {default: [{message: {foo: 'bar'}, middlewares: []}]}};
        renderWithProviders(<QueuePanel data={data} />);
        expect(screen.getByText('Pushes')).toBeInTheDocument();
    });

    it('renders statuses tab with status chips', async () => {
        const user = userEvent.setup();
        const data = {
            ...emptyData,
            statuses: [{id: 'msg-1', status: 'done'}],
            pushes: {q: [{message: {}, middlewares: []}]},
        };
        renderWithProviders(<QueuePanel data={data} />);
        await user.click(screen.getByText('Statuses'));
        expect(screen.getByText('msg-1')).toBeInTheDocument();
        expect(screen.getByText('DONE')).toBeInTheDocument();
    });

    it('renders both messages and queue tabs when both exist', () => {
        const data = {
            pushes: {default: [{message: {}, middlewares: []}]},
            statuses: [],
            processingMessages: {},
            messages: [makeMessage()],
        };
        renderWithProviders(<QueuePanel data={data} />);
        expect(screen.getByText('Messages')).toBeInTheDocument();
        expect(screen.getByText('Pushes')).toBeInTheDocument();
    });
});
