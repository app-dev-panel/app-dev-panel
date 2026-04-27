import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {SsrPanel} from './SsrPanel';

describe('SsrPanel', () => {
    it('renders the supplied HTML verbatim', () => {
        const html = '<div data-testid="ssr-marker"><span class="badge">INFO</span> Hello from backend</div>';
        renderWithProviders(<SsrPanel html={html} />);

        const marker = screen.getByTestId('ssr-marker');
        expect(marker).toBeInTheDocument();
        expect(marker.querySelector('.badge')?.textContent).toBe('INFO');
        expect(marker.textContent).toContain('Hello from backend');
    });

    it('renders empty HTML without throwing', () => {
        renderWithProviders(<SsrPanel html="" />);
        // Component renders an empty MUI Box; assert no crash by querying for any text we expect to be absent
        expect(screen.queryByTestId('ssr-marker')).not.toBeInTheDocument();
    });
});
