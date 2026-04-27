import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {SsrPanel} from './SsrPanel';

// Slot-rendering integration (does the JSON `<script>` get stripped, is the
// React component mounted into the slot element, etc.) is exercised end-to-end
// against a running playground via Playwright. Here we only cover host
// mechanics that don't depend on the registry's components having Redux
// reducers in the test store.
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
        expect(screen.queryByTestId('ssr-marker')).not.toBeInTheDocument();
    });

    it('preserves unknown slots and warns in dev', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
        const html = '<div data-testid="ssr-host"><span data-adp-slot="totally-made-up">fallback</span></div>';
        renderWithProviders(<SsrPanel html={html} />);

        const slot = screen.getByTestId('ssr-host').querySelector('[data-adp-slot="totally-made-up"]')!;
        // Unknown slots stay as-is so the user still sees the fallback content.
        expect(slot.textContent).toBe('fallback');
        expect(warn).toHaveBeenCalled();
        warn.mockRestore();
    });
});
