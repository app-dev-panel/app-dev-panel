import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {StackTrace} from './StackTrace';

const sampleTrace = [
    '#0 /src/app.php(42): App\\Controller->handle()',
    '#1 /src/index.php(10): App\\Kernel->run()',
    '#2 {main}',
].join('\n');

describe('StackTrace', () => {
    it('renders empty trace', () => {
        const {container} = renderWithProviders(<StackTrace trace="" />);
        expect(container.textContent).toBe('');
    });

    it('parses and renders file frames as links', () => {
        renderWithProviders(<StackTrace trace={sampleTrace} />);
        expect(screen.getByText(/\/src\/app\.php\(42\)/)).toBeInTheDocument();
        expect(screen.getByText(/\/src\/index\.php\(10\)/)).toBeInTheDocument();
    });

    it('renders frame file links to file explorer', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />);
        const link = screen.getByText('/src/app.php(42)');
        expect(link.closest('a')).toHaveAttribute('href', '/inspector/files?path=/src/app.php#L42');
    });

    it('renders function calls', () => {
        renderWithProviders(<StackTrace trace={sampleTrace} />);
        expect(screen.getByText('App\\Controller->handle()')).toBeInTheDocument();
        expect(screen.getByText('App\\Kernel->run()')).toBeInTheDocument();
    });

    it('renders {main} frame as plain text', () => {
        renderWithProviders(<StackTrace trace="#0 {main}" />);
        expect(screen.getByText('#0 {main}')).toBeInTheDocument();
    });

    it('renders frame indices', () => {
        renderWithProviders(<StackTrace trace={sampleTrace} />);
        expect(screen.getByText('#0')).toBeInTheDocument();
        expect(screen.getByText('#1')).toBeInTheDocument();
    });

    it('does not show editor buttons when editor is none', () => {
        renderWithProviders(<StackTrace trace={sampleTrace} />);
        expect(screen.queryByText('edit')).not.toBeInTheDocument();
    });

    it('shows editor buttons when editor is configured', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        expect(screen.getByText('edit')).toBeInTheDocument();
    });

    it('editor button has correct href', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        const editButton = screen.getByText('edit').closest('a');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2Fsrc%2Fapp.php:42');
    });

    it('handles multiline traces with varied formats', () => {
        const trace = [
            '#0 /vendor/framework/Router.php(256): dispatch()',
            '#1 /src/Middleware.php(18): App\\Middleware->process()',
            '#2 [internal function]: call_user_func()',
            '#3 {main}',
        ].join('\n');
        renderWithProviders(<StackTrace trace={trace} />);
        expect(screen.getByText(/Router\.php\(256\)/)).toBeInTheDocument();
        expect(screen.getByText(/Middleware\.php\(18\)/)).toBeInTheDocument();
        // Internal function line is rendered as plain text
        expect(screen.getByText('#2 [internal function]: call_user_func()')).toBeInTheDocument();
        expect(screen.getByText('#3 {main}')).toBeInTheDocument();
    });

    it('applies custom fontSize', () => {
        const {container} = renderWithProviders(<StackTrace trace="#0 /src/app.php(1): test()" fontSize={14} />);
        const traceContainer = container.firstElementChild as HTMLElement;
        expect(traceContainer.style.fontSize).toBe('14pt');
    });

    it('uses default fontSize of 10pt', () => {
        const {container} = renderWithProviders(<StackTrace trace="#0 /src/app.php(1): test()" />);
        const traceContainer = container.firstElementChild as HTMLElement;
        expect(traceContainer.style.fontSize).toBe('10pt');
    });

    it('applies path mapping to editor URLs', () => {
        renderWithProviders(<StackTrace trace="#0 /app/src/Controller.php(15): handle()" />, {
            preloadedState: {
                application: {
                    baseUrl: '',
                    editorConfig: {
                        editor: 'vscode',
                        customUrlTemplate: '',
                        pathMapping: {'/app': '/Users/dev/project'},
                    },
                },
            },
        });
        const editButton = screen.getByText('edit').closest('a');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2FUsers%2Fdev%2Fproject%2Fsrc%2FController.php:15');
    });
});
