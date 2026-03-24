import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {EnvironmentPanel} from './EnvironmentPanel';

type PhpInfo = {
    version: string;
    sapi: string;
    binary: string;
    os: string;
    cwd: string | null;
    extensions: string[];
    xdebug: string | false;
    opcache: string | false;
    pcov: string | false;
    ini: {
        loaded: string | null;
        scanned: string | null;
        memory_limit: string | null;
        max_execution_time: string | null;
        display_errors: string | null;
        error_reporting: number;
    };
    zend_extensions: string[];
};

type OsInfo = {family: string; name: string; uname: string; hostname: string | null};

type GitInfo = {branch: string | null; commit: string | null; commitFull: string | null};

type EnvironmentData = {
    php: PhpInfo;
    os: OsInfo;
    git: GitInfo;
    server: Record<string, string>;
    env: Record<string, string>;
};

const makePhpInfo = (overrides: Partial<PhpInfo> = {}): PhpInfo => ({
    version: '8.4.1',
    sapi: 'cli',
    binary: '/usr/bin/php',
    os: 'Linux',
    cwd: '/var/www',
    extensions: ['json', 'mbstring', 'openssl'],
    xdebug: false,
    opcache: '8.4.1',
    pcov: false,
    ini: {
        loaded: '/etc/php/php.ini',
        scanned: '/etc/php/conf.d',
        memory_limit: '256M',
        max_execution_time: '30',
        display_errors: 'On',
        error_reporting: 32767,
    },
    zend_extensions: [],
    ...overrides,
});

const makeOsInfo = (overrides: Partial<OsInfo> = {}): OsInfo => ({
    family: 'Linux',
    name: 'Ubuntu 24.04',
    uname: 'Linux server 6.1.0',
    hostname: 'dev-server',
    ...overrides,
});

const makeGitInfo = (overrides: Partial<GitInfo> = {}): GitInfo => ({
    branch: 'main',
    commit: 'abc1234',
    commitFull: 'abc1234567890abcdef1234567890abcdef123456',
    ...overrides,
});

const makeEnvironmentData = (overrides: Partial<EnvironmentData> = {}): EnvironmentData => ({
    php: makePhpInfo(),
    os: makeOsInfo(),
    git: makeGitInfo(),
    server: {SERVER_NAME: 'localhost', SERVER_PORT: '8080'},
    env: {APP_ENV: 'dev', APP_DEBUG: '1'},
    ...overrides,
});

describe('EnvironmentPanel', () => {
    it('renders nothing when data is null', () => {
        const {container} = renderWithProviders(<EnvironmentPanel data={null as any} />);
        expect(container.innerHTML).toBe('');
    });

    it('renders tabs for PHP & OS, Server, and Environment', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('PHP & OS')).toBeInTheDocument();
        expect(screen.getByText('Server (2)')).toBeInTheDocument();
        expect(screen.getByText('Environment (2)')).toBeInTheDocument();
    });

    it('renders PHP version in the runtime table', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('8.4.1')).toBeInTheDocument();
    });

    it('renders SAPI value', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('cli')).toBeInTheDocument();
    });

    it('renders OS info combined', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('Linux (Ubuntu 24.04)')).toBeInTheDocument();
    });

    it('renders debug extension chips with enabled status', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('OPcache 8.4.1')).toBeInTheDocument();
        expect(screen.getByText('Xdebug')).toBeInTheDocument();
        expect(screen.getByText('PCOV')).toBeInTheDocument();
    });

    it('renders loaded extensions count and chips', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('Loaded Extensions (3)')).toBeInTheDocument();
        expect(screen.getByText('json')).toBeInTheDocument();
        expect(screen.getByText('mbstring')).toBeInTheDocument();
        expect(screen.getByText('openssl')).toBeInTheDocument();
    });

    it('switches to Server tab and renders server parameters', async () => {
        const user = userEvent.setup();
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        await user.click(screen.getByText('Server (2)'));
        expect(screen.getByText('Server Parameters')).toBeInTheDocument();
        expect(screen.getByText('SERVER_NAME')).toBeInTheDocument();
        expect(screen.getByText('localhost')).toBeInTheDocument();
    });

    it('switches to Environment tab and renders env variables', async () => {
        const user = userEvent.setup();
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        await user.click(screen.getByText('Environment (2)'));
        expect(screen.getByText('Environment Variables')).toBeInTheDocument();
        expect(screen.getByText('APP_ENV')).toBeInTheDocument();
        expect(screen.getByText('dev')).toBeInTheDocument();
    });

    it('filters server parameters by key', async () => {
        const user = userEvent.setup();
        const data = makeEnvironmentData({server: {SERVER_NAME: 'localhost', REMOTE_ADDR: '127.0.0.1'}});
        renderWithProviders(<EnvironmentPanel data={data} />);
        await user.click(screen.getByText('Server (2)'));
        const input = screen.getByRole('textbox');
        await user.type(input, 'REMOTE');
        expect(screen.getByText('REMOTE_ADDR')).toBeInTheDocument();
        expect(screen.queryByText('SERVER_NAME')).not.toBeInTheDocument();
    });

    it('shows no matching entries when filter has no results', async () => {
        const user = userEvent.setup();
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        await user.click(screen.getByText('Server (2)'));
        const input = screen.getByRole('textbox');
        await user.type(input, 'nonexistent_key');
        expect(screen.getByText('No matching entries')).toBeInTheDocument();
    });

    it('renders zend extensions section when present', () => {
        const data = makeEnvironmentData({php: makePhpInfo({zend_extensions: ['Zend OPcache']})});
        renderWithProviders(<EnvironmentPanel data={data} />);
        expect(screen.getByText('Zend Extensions')).toBeInTheDocument();
        expect(screen.getByText('Zend OPcache')).toBeInTheDocument();
    });

    it('renders git info section with branch and commit', () => {
        renderWithProviders(<EnvironmentPanel data={makeEnvironmentData()} />);
        expect(screen.getByText('Git')).toBeInTheDocument();
        expect(screen.getByText('main')).toBeInTheDocument();
        expect(screen.getByText('abc1234567890abcdef1234567890abcdef123456')).toBeInTheDocument();
    });

    it('hides git section when git info is null', () => {
        const data = makeEnvironmentData({git: makeGitInfo({branch: null, commit: null, commitFull: null})});
        renderWithProviders(<EnvironmentPanel data={data} />);
        expect(screen.queryByText('Git')).not.toBeInTheDocument();
    });
});
