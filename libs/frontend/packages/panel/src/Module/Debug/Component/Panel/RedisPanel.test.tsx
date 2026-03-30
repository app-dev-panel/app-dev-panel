import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {RedisPanel} from './RedisPanel';

const makeCommand = (
    overrides: Partial<{
        connection: string;
        command: string;
        arguments: unknown[];
        result: unknown;
        duration: number;
        error: string | null;
        line: string;
    }> = {},
) => ({
    connection: 'default',
    command: 'GET',
    arguments: ['user:42'],
    result: 'John',
    duration: 0.0015,
    error: null,
    line: '',
    ...overrides,
});

const makeData = (
    overrides: Partial<{
        commands: any[];
        totalTime: number;
        errorCount: number;
        totalCommands: number;
        connections: string[];
    }> = {},
) => ({
    commands: [makeCommand()],
    totalTime: 0.0015,
    errorCount: 0,
    totalCommands: 1,
    connections: ['default'],
    ...overrides,
});

describe('RedisPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<RedisPanel data={null as any} />);
        expect(screen.getAllByText(/No Redis commands/).length).toBeGreaterThan(0);
    });

    it('shows empty message when totalCommands is 0', () => {
        renderWithProviders(<RedisPanel data={makeData({totalCommands: 0})} />);
        expect(screen.getAllByText(/No Redis commands/).length).toBeGreaterThan(0);
    });

    it('renders summary cards', () => {
        renderWithProviders(
            <RedisPanel data={makeData({totalCommands: 5, errorCount: 1, connections: ['default', 'cache']})} />,
        );
        expect(screen.getByText('Commands')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.getByText('Errors')).toBeInTheDocument();
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('Connections')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders command count in section title', () => {
        renderWithProviders(<RedisPanel data={makeData()} />);
        expect(screen.getByText('1 commands')).toBeInTheDocument();
    });

    it('renders command arguments in row', () => {
        renderWithProviders(<RedisPanel data={makeData({commands: [makeCommand({arguments: ['session:abc']})]})} />);
        expect(screen.getByText('session:abc')).toBeInTheDocument();
    });

    it('renders command chip with correct label', () => {
        const cmds = [makeCommand({command: 'SET'}), makeCommand({command: 'DEL', arguments: ['key2']})];
        renderWithProviders(<RedisPanel data={makeData({commands: cmds, totalCommands: 2})} />);
        expect(screen.getByText('SET')).toBeInTheDocument();
        expect(screen.getByText('DEL')).toBeInTheDocument();
    });

    it('renders ERR chip for commands with errors', () => {
        const cmds = [makeCommand({error: 'WRONGTYPE', arguments: ['bad:key']})];
        renderWithProviders(<RedisPanel data={makeData({commands: cmds, errorCount: 1})} />);
        expect(screen.getByText('ERR')).toBeInTheDocument();
    });

    it('renders connection chip when multiple connections', () => {
        const cmds = [makeCommand({connection: 'redis-1'}), makeCommand({connection: 'redis-2', arguments: ['k2']})];
        renderWithProviders(
            <RedisPanel data={makeData({commands: cmds, totalCommands: 2, connections: ['redis-1', 'redis-2']})} />,
        );
        expect(screen.getAllByText('redis-1').length).toBeGreaterThan(0);
        expect(screen.getAllByText('redis-2').length).toBeGreaterThan(0);
    });

    it('filters commands by command name', async () => {
        const user = userEvent.setup();
        const cmds = [
            makeCommand({command: 'SET', arguments: ['key1']}),
            makeCommand({command: 'GET', arguments: ['key2']}),
        ];
        renderWithProviders(<RedisPanel data={makeData({commands: cmds, totalCommands: 2})} />);
        await user.type(screen.getByPlaceholderText('Filter commands...'), 'SET');
        expect(screen.getByText('key1')).toBeInTheDocument();
        expect(screen.queryByText('key2')).not.toBeInTheDocument();
    });

    it('expands command to show details on click', async () => {
        const user = userEvent.setup();
        const cmds = [makeCommand({arguments: ['user:42'], result: {name: 'John'}})];
        renderWithProviders(<RedisPanel data={makeData({commands: cmds})} />);
        await user.click(screen.getByText('user:42'));
        expect(screen.getByText('Arguments')).toBeInTheDocument();
        expect(screen.getByText('Result')).toBeInTheDocument();
    });

    it('shows error detail in expanded view', async () => {
        const user = userEvent.setup();
        const cmds = [makeCommand({error: 'WRONGTYPE Operation', arguments: ['bad:key']})];
        renderWithProviders(<RedisPanel data={makeData({commands: cmds, errorCount: 1})} />);
        await user.click(screen.getByText('bad:key'));
        expect(screen.getByText('Error')).toBeInTheDocument();
        expect(screen.getByText('WRONGTYPE Operation')).toBeInTheDocument();
    });

    it('shows source line in expanded view', async () => {
        const user = userEvent.setup();
        const cmds = [makeCommand({line: '/app/src/Service.php:42', arguments: ['key1']})];
        renderWithProviders(<RedisPanel data={makeData({commands: cmds})} />);
        await user.click(screen.getByText('key1'));
        expect(screen.getByText('Source')).toBeInTheDocument();
        expect(screen.getByText('/app/src/Service.php:42')).toBeInTheDocument();
    });

    it('renders connection breakdown when multiple connections exist', () => {
        const cmds = [makeCommand({connection: 'default'}), makeCommand({connection: 'cache', arguments: ['k2']})];
        renderWithProviders(
            <RedisPanel data={makeData({commands: cmds, totalCommands: 2, connections: ['default', 'cache']})} />,
        );
        // "Connections" appears in summary card + breakdown section
        expect(screen.getAllByText('Connections').length).toBeGreaterThanOrEqual(2);
    });
});
