import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {FileLink} from './FileLink';

describe('FileLink', () => {
    it('returns null when neither path nor className provided', () => {
        const {container} = renderWithProviders(<FileLink />);
        expect(container.innerHTML).toBe('');
    });

    it('renders children with explorer link for path', () => {
        renderWithProviders(<FileLink path="/src/app.php:42">app.php:42</FileLink>);
        const link = screen.getByText('app.php:42');
        expect(link).toBeInTheDocument();
        expect(link.closest('a')).toHaveAttribute('href', '/inspector/files?path=/src/app.php#L42');
    });

    it('renders children with explorer link for path without line', () => {
        renderWithProviders(<FileLink path="/src/app.php">app.php</FileLink>);
        const link = screen.getByText('app.php');
        expect(link.closest('a')).toHaveAttribute('href', '/inspector/files?path=/src/app.php');
    });

    it('renders explorer link for className', () => {
        renderWithProviders(<FileLink className={'App\\Controller\\HomeController'}>HomeController</FileLink>);
        const link = screen.getByText('HomeController');
        const href = link.closest('a')?.getAttribute('href') ?? '';
        expect(href).toMatch(/^\/inspector\/files\?class=/);
        expect(href).toContain('class=App');
        expect(href).toContain('Controller');
    });

    it('renders explorer link for className with methodName', () => {
        renderWithProviders(
            <FileLink className={'App\\Controller'} methodName="index">
                Controller::index
            </FileLink>,
        );
        const link = screen.getByText('Controller::index');
        const href = link.closest('a')?.getAttribute('href') ?? '';
        expect(href).toContain('/inspector/files?');
        expect(href).toContain('method=index');
        expect(href).toContain('class=App');
    });

    it('does not render editor button when editor is none (default)', () => {
        renderWithProviders(<FileLink path="/src/app.php">app.php</FileLink>);
        expect(screen.queryByText('edit')).not.toBeInTheDocument();
    });

    it('renders editor button when editor is configured', () => {
        renderWithProviders(<FileLink path="/src/app.php">app.php</FileLink>, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        expect(screen.getByText('edit')).toBeInTheDocument();
    });

    it('editor button has correct href', () => {
        renderWithProviders(
            <FileLink path="/src/app.php" line={42}>
                app.php
            </FileLink>,
            {
                preloadedState: {
                    application: {
                        baseUrl: '',
                        editorConfig: {editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}},
                    },
                },
            },
        );
        const editButton = screen.getByText('edit').closest('a');
        expect(editButton).toHaveAttribute('href', 'phpstorm://open?file=%2Fsrc%2Fapp.php&line=42');
    });

    it('extracts line from path when no explicit line prop', () => {
        renderWithProviders(<FileLink path="/src/app.php:99">app.php</FileLink>, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        const editButton = screen.getByText('edit').closest('a');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2Fsrc%2Fapp.php:99');
    });

    it('explicit line prop overrides line from path', () => {
        renderWithProviders(
            <FileLink path="/src/app.php:99" line={5}>
                app.php
            </FileLink>,
            {
                preloadedState: {
                    application: {
                        baseUrl: '',
                        editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}},
                    },
                },
            },
        );
        const editButton = screen.getByText('edit').closest('a');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2Fsrc%2Fapp.php:5');
    });

    it('does not render editor button for className-only link (no path)', () => {
        renderWithProviders(<FileLink className="App\\Controller">Controller</FileLink>, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        // No path means no editor URL can be generated
        expect(screen.queryByText('edit')).not.toBeInTheDocument();
    });

    it('renders without children (editor-only mode)', () => {
        const {container} = renderWithProviders(<FileLink path="/src/app.php" />, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        // No anchor for children but editor button exists
        expect(container.querySelector('a[href^="/inspector"]')).toBeNull();
        expect(screen.getByText('edit')).toBeInTheDocument();
    });

    it('applies path mapping in editor URL', () => {
        renderWithProviders(
            <FileLink path="/app/src/Controller.php" line={10}>
                Controller
            </FileLink>,
            {
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
            },
        );
        const editButton = screen.getByText('edit').closest('a');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2FUsers%2Fdev%2Fproject%2Fsrc%2FController.php:10');
    });
});
