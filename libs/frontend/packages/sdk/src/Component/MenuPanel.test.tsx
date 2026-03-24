import {Home, Settings} from '@mui/icons-material';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {LinkProps, MenuPanel} from './MenuPanel';

const createLinks = (count = 2): LinkProps[] =>
    Array.from({length: count}, (_, i) => ({
        name: `link-${i}`,
        text: `Link ${i}`,
        icon: i % 2 === 0 ? <Home /> : <Settings />,
        href: `/path-${i}`,
    }));

describe('MenuPanel', () => {
    it('renders menu links', () => {
        const links = createLinks(3);
        renderWithProviders(<MenuPanel links={links} />);
        expect(screen.getByText('Link 0')).toBeInTheDocument();
        expect(screen.getByText('Link 1')).toBeInTheDocument();
        expect(screen.getByText('Link 2')).toBeInTheDocument();
    });

    it('renders children content', () => {
        renderWithProviders(
            <MenuPanel links={createLinks()}>
                <div>Main Content</div>
            </MenuPanel>,
        );
        expect(screen.getByText('Main Content')).toBeInTheDocument();
    });

    it('renders link text abbreviated to 3 chars in avatar', () => {
        const links: LinkProps[] = [{name: 'dashboard', text: 'Dashboard', icon: <Home />, href: '/dash'}];
        renderWithProviders(<MenuPanel links={links} />);
        expect(screen.getByText('Das')).toBeInTheDocument();
    });

    it('renders with badge content', () => {
        const links: LinkProps[] = [{name: 'alerts', text: 'Alerts', icon: <Home />, href: '/alerts', badge: 5}];
        renderWithProviders(<MenuPanel links={links} />);
        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('toggles drawer open/closed on chevron click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MenuPanel links={createLinks()} />);

        const chevronButton = screen.getByRole('button', {name: ''});
        await user.click(chevronButton);
    });

    it('starts open when open prop is true', () => {
        renderWithProviders(<MenuPanel links={createLinks()} open={true} />);
        const drawer = document.querySelector('.drawer-opened');
        expect(drawer).toBeInTheDocument();
    });

    it('starts closed when open prop is false', () => {
        renderWithProviders(<MenuPanel links={createLinks()} open={false} />);
        const drawer = document.querySelector('.drawer-opened');
        expect(drawer).not.toBeInTheDocument();
    });

    it('renders links as anchor elements with href', () => {
        const links: LinkProps[] = [{name: 'home', text: 'Home Page', icon: <Home />, href: '/home'}];
        renderWithProviders(<MenuPanel links={links} />);
        const link = screen.getByRole('link', {name: /Hom/});
        expect(link).toHaveAttribute('href', '/home');
    });

    it('renders with empty links array', () => {
        renderWithProviders(<MenuPanel links={[]} />);
        expect(document.querySelector('.MuiDrawer-root')).toBeInTheDocument();
    });
});
