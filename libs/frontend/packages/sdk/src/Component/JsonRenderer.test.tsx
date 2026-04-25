import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {ClosureDescriptor, JsonRenderer} from './JsonRenderer';

const makeClosure = (source: string): ClosureDescriptor => ({
    __closure: true,
    source,
    file: '/app/src/Example.php',
    startLine: 10,
    endLine: 12,
});

describe('JsonRenderer', () => {
    it('renders top-level closure descriptor as syntax-highlighted code', () => {
        const descriptor = makeClosure('static fn($x) => $x + 1');

        const {container} = renderWithProviders(<JsonRenderer value={descriptor} />);

        expect(container.textContent).toContain('static fn($x) => $x + 1');
        expect(container.querySelector('pre')).toBeInTheDocument();
    });

    it('renders string that looks like a PHP function as code', () => {
        const source = 'static fn(\\App\\Foo $foo) => new \\App\\Bar($foo)';

        const {container} = renderWithProviders(<JsonRenderer value={source} />);

        expect(container.textContent).toContain(source);
        expect(container.querySelector('pre')).toBeInTheDocument();
    });

    it('renders short-arrow function without space after fn as code', () => {
        const source = 'fn(int $x) => $x * 2';

        const {container} = renderWithProviders(<JsonRenderer value={source} />);

        expect(container.querySelector('pre')).toBeInTheDocument();
        expect(container.textContent).toContain(source);
    });

    it('renders nested closure descriptor inside array value as code', () => {
        const value = {
            definition: makeClosure('static function () { return 1; }'),
            reset: makeClosure('function () use ($x) { $x->enable(); }'),
        };

        const {container} = renderWithProviders(<JsonRenderer value={value} />);

        expect(container.textContent).toContain('static function () { return 1; }');
        expect(container.textContent).toContain('function () use ($x) { $x->enable(); }');
        expect(container.querySelectorAll('pre').length).toBeGreaterThanOrEqual(2);
    });

    it('preserves non-closure values in arrays', () => {
        const value = {name: 'Service', count: 3};

        renderWithProviders(<JsonRenderer value={value} />);

        expect(screen.getByText('name')).toBeInTheDocument();
        expect(screen.getByText('count')).toBeInTheDocument();
    });

    it('renders plain primitives unchanged', () => {
        renderWithProviders(<JsonRenderer value={42} />);
        expect(screen.getByText('42')).toBeInTheDocument();
    });

    it('renders @-prefixed string as an inline alias chip', () => {
        const {container} = renderWithProviders(<JsonRenderer value="@root/src" />);

        expect(screen.getByText('alias')).toBeInTheDocument();
        expect(screen.getByText('@root/src')).toBeInTheDocument();

        const chip = container.querySelector('span.MuiBox-root');
        expect(chip).not.toBeNull();
        expect(window.getComputedStyle(chip as HTMLElement).display).toBe('inline-flex');
    });

    it('renders an alias nested inside an object (JsonView.String path) as an alias chip', () => {
        renderWithProviders(<JsonRenderer value={{'@src': '@root/src'}} />);

        expect(screen.getByText('alias')).toBeInTheDocument();
        expect(screen.getByText('@root/src')).toBeInTheDocument();
    });

    it('renders multiple aliases in the same tree each as a distinct alias chip', () => {
        renderWithProviders(
            <JsonRenderer value={{'@src': '@root/src', '@assets': '@root/public/assets', '@vendor': '@root/vendor'}} />,
        );

        expect(screen.getAllByText('alias')).toHaveLength(3);
        expect(screen.getByText('@root/src')).toBeInTheDocument();
        expect(screen.getByText('@root/public/assets')).toBeInTheDocument();
        expect(screen.getByText('@root/vendor')).toBeInTheDocument();
    });

    it('renders non-alias primitive strings without the alias chip', () => {
        renderWithProviders(<JsonRenderer value="just a string" />);

        expect(screen.queryByText('alias')).not.toBeInTheDocument();
        expect(screen.getByText('just a string')).toBeInTheDocument();
    });

    it('does not render a string that merely contains @ but does not start with @ as an alias', () => {
        renderWithProviders(<JsonRenderer value="user@example.com" />);

        expect(screen.queryByText('alias')).not.toBeInTheDocument();
        expect(screen.getByText('user@example.com')).toBeInTheDocument();
    });
});
