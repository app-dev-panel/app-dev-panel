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

    it('parses and renders short filenames with line numbers', () => {
        renderWithProviders(<StackTrace trace={sampleTrace} />);
        expect(screen.getByText('app.php:42')).toBeInTheDocument();
        expect(screen.getByText('index.php:10')).toBeInTheDocument();
    });

    it('renders frame file links to file explorer', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />);
        const link = screen.getByText('app.php:42');
        expect(link.closest('a')).toHaveAttribute('href', '/inspector/files?path=/src/app.php#L42');
    });

    it('renders class method calls as links', () => {
        renderWithProviders(<StackTrace trace={sampleTrace} />);
        const controllerLink = screen.getByText('App\\Controller::handle');
        expect(controllerLink.closest('a')).toHaveAttribute(
            'href',
            '/inspector/files?class=App%5CController&method=handle',
        );
        const kernelLink = screen.getByText('App\\Kernel::run');
        expect(kernelLink.closest('a')).toHaveAttribute('href', '/inspector/files?class=App%5CKernel&method=run');
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
        expect(screen.queryByLabelText('Open in Editor')).not.toBeInTheDocument();
    });

    it('shows editor buttons when editor is configured', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        expect(screen.getByLabelText('Open in Editor')).toBeInTheDocument();
    });

    it('editor button has correct href', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        const editButton = screen.getByLabelText('Open in Editor');
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
        expect(screen.getByText('Router.php:256')).toBeInTheDocument();
        expect(screen.getByText('Middleware.php:18')).toBeInTheDocument();
        // Internal function line is rendered as plain text
        expect(screen.getByText('#2 [internal function]: call_user_func()')).toBeInTheDocument();
        expect(screen.getByText('#3 {main}')).toBeInTheDocument();
    });

    it('renders vendor frames with short filename', () => {
        const trace = '#0 /vendor/framework/Router.php(10): dispatch()';
        renderWithProviders(<StackTrace trace={trace} />);
        expect(screen.getByText('Router.php:10')).toBeInTheDocument();
    });

    it('renders Object() class arguments as links', () => {
        const trace = '#0 /src/app.php(42): App\\Controller->handle(Object(Symfony\\Http\\Request), 1)';
        renderWithProviders(<StackTrace trace={trace} />);
        const objLink = screen.getByText('Symfony\\Http\\Request');
        expect(objLink.closest('a')).toHaveAttribute('href', '/inspector/files?class=Symfony%5CHttp%5CRequest');
    });

    it('renders plain function calls as text', () => {
        renderWithProviders(<StackTrace trace="#0 /src/app.php(42): doStuff()" />);
        expect(screen.getByText('doStuff()')).toBeInTheDocument();
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
        const editButton = screen.getByLabelText('Open in Editor');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2FUsers%2Fdev%2Fproject%2Fsrc%2FController.php:15');
    });
});
