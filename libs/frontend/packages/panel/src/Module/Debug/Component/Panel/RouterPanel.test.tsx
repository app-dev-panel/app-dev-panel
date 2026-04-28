import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {RouterPanel} from './RouterPanel';

const makeCurrentRoute = (
    overrides: Partial<{
        matchTime: number;
        name: string | null;
        pattern: string;
        arguments: Record<string, string> | null;
        host: string | null;
        uri: string;
        action: any;
        middlewares: any[];
    }> = {},
) => ({
    matchTime: 0.5,
    name: 'app_home',
    pattern: '/home',
    arguments: null,
    host: null,
    uri: '/home',
    action: null,
    middlewares: [],
    ...overrides,
});

const makeRoute = (overrides: Partial<{name: string; pattern: string; methods: string[]; host: string}> = {}) => ({
    name: 'app_home',
    pattern: '/home',
    methods: ['GET'],
    ...overrides,
});

describe('RouterPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<RouterPanel data={null as any} />);
        expect(screen.getByText(/No router data found/)).toBeInTheDocument();
        expect(screen.getByText(/RouterCollector did not capture any data/)).toBeInTheDocument();
    });

    it('shows empty message when data is an empty object', () => {
        renderWithProviders(<RouterPanel data={{} as any} />);
        expect(screen.getByText(/No router data found/)).toBeInTheDocument();
    });

    it('shows empty message when data is an empty array (PHP `[]`)', () => {
        renderWithProviders(<RouterPanel data={[] as any} />);
        expect(screen.getByText(/No router data found/)).toBeInTheDocument();
    });

    it('shows empty message when no route matched', () => {
        renderWithProviders(<RouterPanel data={{currentRoute: null, routes: []}} />);
        expect(screen.getByText(/No route matched/)).toBeInTheDocument();
        expect(screen.getByText(/No route was matched for this request/)).toBeInTheDocument();
    });

    it('shows "no route matched" when currentRoute is an object with no usable fields', () => {
        renderWithProviders(
            <RouterPanel data={{currentRoute: {pattern: '', uri: '', name: null} as any, routes: []}} />,
        );
        expect(screen.getByText(/No route matched/)).toBeInTheDocument();
    });

    it('renders Current Route section', () => {
        renderWithProviders(<RouterPanel data={{currentRoute: makeCurrentRoute(), routes: []}} />);
        expect(screen.getByText('Current Route')).toBeInTheDocument();
    });

    it('renders route name', () => {
        renderWithProviders(
            <RouterPanel data={{currentRoute: makeCurrentRoute({name: 'user_profile'}), routes: []}} />,
        );
        expect(screen.getByText('user_profile')).toBeInTheDocument();
    });

    it('renders pattern', () => {
        renderWithProviders(
            <RouterPanel data={{currentRoute: makeCurrentRoute({pattern: '/users/{id}'}), routes: []}} />,
        );
        expect(screen.getByText('/users/{id}')).toBeInTheDocument();
    });

    it('renders URI', () => {
        renderWithProviders(<RouterPanel data={{currentRoute: makeCurrentRoute({uri: '/users/42'}), routes: []}} />);
        expect(screen.getByText('/users/42')).toBeInTheDocument();
    });

    it('renders host when present', () => {
        renderWithProviders(
            <RouterPanel data={{currentRoute: makeCurrentRoute({host: 'api.example.com'}), routes: []}} />,
        );
        expect(screen.getByText('api.example.com')).toBeInTheDocument();
    });

    it('renders route arguments when present', () => {
        renderWithProviders(
            <RouterPanel data={{currentRoute: makeCurrentRoute({arguments: {id: '42', slug: 'test'}}), routes: []}} />,
        );
        expect(screen.getByText('Arguments')).toBeInTheDocument();
        expect(screen.getByText('id:')).toBeInTheDocument();
        expect(screen.getByText('42')).toBeInTheDocument();
    });

    it('renders routes list with count', () => {
        const routes = [makeRoute(), makeRoute({name: 'about', pattern: '/about'})];
        renderWithProviders(<RouterPanel data={{currentRoute: null, routes}} />);
        expect(screen.getByText('2 routes')).toBeInTheDocument();
    });

    it('renders method chips', () => {
        const routes = [makeRoute({methods: ['GET', 'POST']})];
        renderWithProviders(<RouterPanel data={{currentRoute: null, routes}} />);
        expect(screen.getByText('GET')).toBeInTheDocument();
        expect(screen.getByText('POST')).toBeInTheDocument();
    });

    it('filters routes by pattern', async () => {
        const user = userEvent.setup();
        const routes = [makeRoute({pattern: '/home'}), makeRoute({name: 'about', pattern: '/about'})];
        renderWithProviders(<RouterPanel data={{currentRoute: null, routes}} />);
        await user.type(screen.getByPlaceholderText('Filter routes...'), 'about');
        expect(screen.getByText('/about')).toBeInTheDocument();
        expect(screen.queryByText('/home')).not.toBeInTheDocument();
    });

    it('renders Inspector button', () => {
        renderWithProviders(<RouterPanel data={{currentRoute: null, routes: [makeRoute()]}} />);
        expect(screen.getByText('Inspector')).toBeInTheDocument();
    });
});
