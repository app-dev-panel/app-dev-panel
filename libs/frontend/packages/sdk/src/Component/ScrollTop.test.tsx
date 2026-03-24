import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {ScrollTopButton} from './ScrollTop';

describe('ScrollTopButton', () => {
    it('renders without crashing (bottomOffset false)', () => {
        const {container} = renderWithProviders(<ScrollTopButton bottomOffset={false} />);
        expect(container).toBeTruthy();
    });

    it('renders without crashing (bottomOffset true)', () => {
        const {container} = renderWithProviders(<ScrollTopButton bottomOffset={true} />);
        expect(container).toBeTruthy();
    });

    it('renders a presentation role element', () => {
        const {container} = renderWithProviders(<ScrollTopButton bottomOffset={false} />);
        const box = container.querySelector('[role="presentation"]');
        expect(box).toBeInTheDocument();
    });
});
