import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {ClassName} from './ClassName';

const FQCN = 'App\\Message\\ProcessPayment';

describe('ClassName', () => {
    it('renders plain text without buttons when value is not a FQCN', () => {
        renderWithProviders(<ClassName value="NotAClass" />);
        expect(screen.getByText('NotAClass')).toBeInTheDocument();
        expect(screen.queryByLabelText('Open in File Explorer')).not.toBeInTheDocument();
        expect(screen.queryByLabelText('Open in Editor')).not.toBeInTheDocument();
    });

    it('renders a File Explorer icon button for a FQCN', () => {
        renderWithProviders(<ClassName value={FQCN} />);
        expect(screen.getByText(FQCN)).toBeInTheDocument();
        const button = screen.getByLabelText('Open in File Explorer');
        expect(button).toHaveAttribute('href', expect.stringMatching(/^\/inspector\/files\?class=/));
    });

    it('includes method name in the File Explorer URL when provided', () => {
        renderWithProviders(<ClassName value="App\Controller\HomeController" methodName="index" />);
        const button = screen.getByLabelText('Open in File Explorer');
        const href = button.getAttribute('href') ?? '';
        expect(href).toContain('class=App');
        expect(href).toContain('method=index');
    });

    it('uses custom children as the link label', () => {
        renderWithProviders(<ClassName value={FQCN}>ProcessPayment</ClassName>);
        expect(screen.getByText('ProcessPayment')).toBeInTheDocument();
        expect(screen.queryByText(FQCN)).not.toBeInTheDocument();
    });

    it('does not render Open in Editor button when editor is not configured', () => {
        renderWithProviders(<ClassName value={FQCN} />);
        expect(screen.queryByLabelText('Open in Editor')).not.toBeInTheDocument();
    });
});
