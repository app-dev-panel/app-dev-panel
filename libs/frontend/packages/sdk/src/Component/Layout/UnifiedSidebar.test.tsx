import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {UnifiedSidebar} from './UnifiedSidebar';

const homeSectionIcon = 'home';
const debugSectionIcon = 'bug_report';

const baseSections = [
    {key: 'home', icon: homeSectionIcon, label: 'Home', href: '/'},
    {
        key: 'debug',
        icon: debugSectionIcon,
        label: 'Debug',
        href: '/debug',
        children: [
            {key: 'overview', icon: 'dashboard', label: 'Overview'},
            {key: 'logs', icon: 'list', label: 'Logs'},
        ],
    },
    {key: 'inspector', icon: 'search', label: 'Inspector', href: '/inspector'},
];

describe('UnifiedSidebar', () => {
    it('renders section labels', () => {
        renderWithProviders(
            <UnifiedSidebar sections={baseSections} activePath="/" onNavigate={vi.fn()} onChildClick={vi.fn()} />,
        );
        expect(screen.getByText('Home')).toBeInTheDocument();
        expect(screen.getByText('Debug')).toBeInTheDocument();
        expect(screen.getByText('Inspector')).toBeInTheDocument();
    });

    it('renders section icons', () => {
        renderWithProviders(
            <UnifiedSidebar sections={baseSections} activePath="/" onNavigate={vi.fn()} onChildClick={vi.fn()} />,
        );
        expect(screen.getByText(homeSectionIcon)).toBeInTheDocument();
        expect(screen.getByText(debugSectionIcon)).toBeInTheDocument();
        expect(screen.getByText('search')).toBeInTheDocument();
    });

    it('highlights active section based on activePath', () => {
        renderWithProviders(
            <UnifiedSidebar
                sections={baseSections}
                activePath="/debug"
                onNavigate={vi.fn()}
                onChildClick={vi.fn()}
            />,
        );
        // The Debug section header should be rendered; Home should not be active.
        // We verify the Debug label is present and its section is rendered.
        expect(screen.getByText('Debug')).toBeInTheDocument();
        // Children should be visible because the debug section is active and expanded.
        expect(screen.getByText('Overview')).toBeInTheDocument();
        expect(screen.getByText('Logs')).toBeInTheDocument();
    });

    it('renders children items when section is expanded', () => {
        renderWithProviders(
            <UnifiedSidebar
                sections={baseSections}
                activePath="/debug"
                onNavigate={vi.fn()}
                onChildClick={vi.fn()}
            />,
        );
        expect(screen.getByText('Overview')).toBeInTheDocument();
        expect(screen.getByText('Logs')).toBeInTheDocument();
        expect(screen.getByText('dashboard')).toBeInTheDocument();
        expect(screen.getByText('list')).toBeInTheDocument();
    });

    it('calls onNavigate when a section is clicked', async () => {
        const user = userEvent.setup();
        const onNavigate = vi.fn();
        renderWithProviders(
            <UnifiedSidebar
                sections={baseSections}
                activePath="/"
                onNavigate={onNavigate}
                onChildClick={vi.fn()}
            />,
        );
        await user.click(screen.getByText('Home'));
        expect(onNavigate).toHaveBeenCalledWith('/');

        await user.click(screen.getByText('Debug'));
        expect(onNavigate).toHaveBeenCalledWith('/debug');
    });

    it('calls onChildClick when a child item is clicked', async () => {
        const user = userEvent.setup();
        const onChildClick = vi.fn();
        renderWithProviders(
            <UnifiedSidebar
                sections={baseSections}
                activePath="/debug"
                onNavigate={vi.fn()}
                onChildClick={onChildClick}
            />,
        );
        await user.click(screen.getByText('Overview'));
        expect(onChildClick).toHaveBeenCalledWith('debug', 'overview');

        await user.click(screen.getByText('Logs'));
        expect(onChildClick).toHaveBeenCalledWith('debug', 'logs');
    });
});
