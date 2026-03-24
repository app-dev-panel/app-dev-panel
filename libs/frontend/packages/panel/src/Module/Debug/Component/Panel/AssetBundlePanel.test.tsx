import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {AssetBundlePanel} from './AssetBundlePanel';

type Bundle = {
    class: string;
    sourcePath: string | null;
    basePath: string | null;
    baseUrl: string | null;
    css: string[];
    js: string[];
    depends: string[];
    options: Record<string, any>;
};

const makeBundle = (overrides: Partial<Bundle> = {}): Bundle => ({
    class: 'App\\Assets\\MainBundle',
    sourcePath: '/var/www/app/assets',
    basePath: '/var/www/public/assets',
    baseUrl: '/assets',
    css: ['css/app.css'],
    js: ['js/app.js'],
    depends: ['App\\Assets\\VendorBundle'],
    options: {},
    ...overrides,
});

const makeData = (bundles: Record<string, Bundle> = {main: makeBundle()}, bundleCount?: number) => ({
    bundles,
    bundleCount: bundleCount ?? Object.keys(bundles).length,
});

describe('AssetBundlePanel', () => {
    it('shows empty message when data is null', () => {
        renderWithProviders(<AssetBundlePanel data={null as any} />);
        expect(screen.getByText(/No asset bundles found/)).toBeInTheDocument();
    });

    it('shows empty message when bundles is empty object', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({})} />);
        expect(screen.getByText(/No asset bundles found/)).toBeInTheDocument();
    });

    it('renders total bundles count in summary card', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({a: makeBundle(), b: makeBundle()}, 2)} />);
        expect(screen.getByText('Total Bundles')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders bundle count in section title', () => {
        renderWithProviders(<AssetBundlePanel data={makeData()} />);
        expect(screen.getByText('1 bundles')).toBeInTheDocument();
    });

    it('renders short class name for bundle row', () => {
        renderWithProviders(
            <AssetBundlePanel data={makeData({main: makeBundle({class: 'App\\Assets\\MainBundle'})})} />,
        );
        expect(screen.getByText('MainBundle')).toBeInTheDocument();
    });

    it('renders CSS count chip', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({main: makeBundle({css: ['a.css', 'b.css']})})} />);
        expect(screen.getByText('2 CSS')).toBeInTheDocument();
    });

    it('renders JS count chip', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({main: makeBundle({js: ['a.js', 'b.js', 'c.js']})})} />);
        expect(screen.getByText('3 JS')).toBeInTheDocument();
    });

    it('renders dependency count chip', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({main: makeBundle({depends: ['Dep\\A', 'Dep\\B']})})} />);
        expect(screen.getByText('2 deps')).toBeInTheDocument();
    });

    it('renders singular dep label for single dependency', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({main: makeBundle({depends: ['Dep\\A']})})} />);
        expect(screen.getByText('1 dep')).toBeInTheDocument();
    });

    it('hides CSS/JS chips when arrays are empty', () => {
        renderWithProviders(<AssetBundlePanel data={makeData({main: makeBundle({css: [], js: [], depends: []})})} />);
        expect(screen.queryByText(/CSS/)).not.toBeInTheDocument();
        expect(screen.queryByText(/JS/)).not.toBeInTheDocument();
        expect(screen.queryByText(/dep/)).not.toBeInTheDocument();
    });

    it('expands bundle detail on click showing full class name', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <AssetBundlePanel data={makeData({main: makeBundle({class: 'App\\Assets\\MainBundle'})})} />,
        );
        await user.click(screen.getByText('MainBundle'));
        expect(screen.getByText('Full Class Name')).toBeInTheDocument();
        expect(screen.getByText('App\\Assets\\MainBundle')).toBeInTheDocument();
    });

    it('shows source path and base path in expanded detail', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <AssetBundlePanel
                data={makeData({
                    main: makeBundle({sourcePath: '/src/assets', basePath: '/public/assets', baseUrl: '/assets'}),
                })}
            />,
        );
        await user.click(screen.getByText('MainBundle'));
        expect(screen.getByText('Source Path')).toBeInTheDocument();
        expect(screen.getByText('/src/assets')).toBeInTheDocument();
        expect(screen.getByText('Base Path')).toBeInTheDocument();
        expect(screen.getByText('/public/assets')).toBeInTheDocument();
        expect(screen.getByText('Base URL')).toBeInTheDocument();
        expect(screen.getByText('/assets')).toBeInTheDocument();
    });

    it('shows CSS and JS files in expanded detail', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <AssetBundlePanel data={makeData({main: makeBundle({css: ['style.css'], js: ['main.js']})})} />,
        );
        await user.click(screen.getByText('MainBundle'));
        expect(screen.getByText('CSS Files')).toBeInTheDocument();
        expect(screen.getByText('style.css')).toBeInTheDocument();
        expect(screen.getByText('JS Files')).toBeInTheDocument();
        expect(screen.getByText('main.js')).toBeInTheDocument();
    });

    it('shows dependency chips in expanded detail', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <AssetBundlePanel data={makeData({main: makeBundle({depends: ['Vendor\\Assets\\JqueryBundle']})})} />,
        );
        await user.click(screen.getByText('MainBundle'));
        expect(screen.getByText('Dependencies')).toBeInTheDocument();
        expect(screen.getByText('JqueryBundle')).toBeInTheDocument();
    });

    it('filters bundles by class name', async () => {
        const user = userEvent.setup();
        const bundles = {
            main: makeBundle({class: 'App\\Assets\\MainBundle'}),
            vendor: makeBundle({class: 'Vendor\\Assets\\JqueryBundle'}),
        };
        renderWithProviders(<AssetBundlePanel data={makeData(bundles)} />);
        await user.type(screen.getByPlaceholderText('Filter bundles...'), 'Jquery');
        expect(screen.getByText('1 bundles')).toBeInTheDocument();
        expect(screen.queryByText('MainBundle')).not.toBeInTheDocument();
        expect(screen.getByText('JqueryBundle')).toBeInTheDocument();
    });
});
