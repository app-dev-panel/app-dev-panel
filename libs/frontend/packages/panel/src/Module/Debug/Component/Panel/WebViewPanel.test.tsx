import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {WebViewPanel} from './WebViewPanel';

const makeEntry = (overrides: Partial<{output: string; file: string; parameters: any[]}> = {}) => ({
    output: '<div>Hello World</div>',
    file: '/app/views/home/index.php',
    parameters: [],
    ...overrides,
});

describe('WebViewPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<WebViewPanel data={[]} />);
        expect(screen.getByText(/No WebView renders found/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<WebViewPanel data={null as any} />);
        expect(screen.getByText(/No WebView renders found/)).toBeInTheDocument();
    });

    it('renders count in section title', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry()]} />);
        expect(screen.getByText('1 renders')).toBeInTheDocument();
    });

    it('renders plural count', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry(), makeEntry({file: '/app/views/about.php'})]} />);
        expect(screen.getByText('2 renders')).toBeInTheDocument();
    });

    it('renders file basename', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry({file: '/app/views/home/index.php'})]} />);
        expect(screen.getByText('index.php')).toBeInTheDocument();
    });

    it('renders directory path', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry({file: '/app/views/home/index.php'})]} />);
        expect(screen.getByText('/app/views/home')).toBeInTheDocument();
    });

    it('renders parameters chip when present', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry({parameters: [{name: 'title'}]})]} />);
        expect(screen.getByText('1 param')).toBeInTheDocument();
    });

    it('renders plural params chip', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry({parameters: [{a: 1}, {b: 2}]})]} />);
        expect(screen.getByText('2 params')).toBeInTheDocument();
    });

    it('does not show params chip when parameters empty', () => {
        renderWithProviders(<WebViewPanel data={[makeEntry({parameters: []})]} />);
        expect(screen.queryByText(/param/)).not.toBeInTheDocument();
    });

    it('expands entry on click showing output', async () => {
        const user = userEvent.setup();
        renderWithProviders(<WebViewPanel data={[makeEntry({output: '<p>Test content</p>'})]} />);
        await user.click(screen.getByText('index.php'));
        expect(screen.getByText('Output')).toBeInTheDocument();
        expect(screen.getByText('<p>Test content</p>')).toBeInTheDocument();
    });

    it('filters by file name', async () => {
        const user = userEvent.setup();
        const data = [makeEntry({file: '/views/home.php'}), makeEntry({file: '/views/about.php'})];
        renderWithProviders(<WebViewPanel data={data} />);
        await user.type(screen.getByPlaceholderText('Filter files...'), 'about');
        expect(screen.getByText('about.php')).toBeInTheDocument();
        expect(screen.queryByText('home.php')).not.toBeInTheDocument();
    });
});
