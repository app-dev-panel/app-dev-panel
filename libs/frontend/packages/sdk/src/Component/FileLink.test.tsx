import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {FileLink} from './FileLink';

describe('FileLink', () => {
    it('returns null when path is empty', () => {
        const {container} = renderWithProviders(<FileLink path="" />);
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

    it('does not render editor button when editor is none (default)', () => {
        renderWithProviders(<FileLink path="/src/app.php">app.php</FileLink>);
        expect(screen.queryByLabelText('Open in Editor')).not.toBeInTheDocument();
    });

    it('renders editor button when editor is configured', () => {
        renderWithProviders(<FileLink path="/src/app.php">app.php</FileLink>, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        expect(screen.getByLabelText('Open in Editor')).toBeInTheDocument();
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
        const editButton = screen.getByLabelText('Open in Editor');
        expect(editButton).toHaveAttribute('href', 'phpstorm://open?file=%2Fsrc%2Fapp.php&line=42');
    });

    it('extracts line from path when no explicit line prop', () => {
        renderWithProviders(<FileLink path="/src/app.php:99">app.php</FileLink>, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        const editButton = screen.getByLabelText('Open in Editor');
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
        const editButton = screen.getByLabelText('Open in Editor');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2Fsrc%2Fapp.php:5');
    });

    it('renders without children (editor-only mode)', () => {
        const {container} = renderWithProviders(<FileLink path="/src/app.php" />, {
            preloadedState: {
                application: {baseUrl: '', editorConfig: {editor: 'vscode', customUrlTemplate: '', pathMapping: {}}},
            },
        });
        // No anchor for children but editor button exists
        expect(container.querySelector('a[href^="/inspector"]')).toBeNull();
        expect(screen.getByLabelText('Open in Editor')).toBeInTheDocument();
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
        const editButton = screen.getByLabelText('Open in Editor');
        expect(editButton).toHaveAttribute('href', 'vscode://file/%2FUsers%2Fdev%2Fproject%2Fsrc%2FController.php:10');
    });
});
