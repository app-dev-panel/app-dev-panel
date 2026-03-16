import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {ContentPanel} from './ContentPanel';

describe('ContentPanel', () => {
    it('renders children', () => {
        renderWithProviders(
            <ContentPanel>
                <div>Test content</div>
            </ContentPanel>,
        );
        expect(screen.getByText('Test content')).toBeInTheDocument();
    });

    it('renders empty without children', () => {
        const {container} = renderWithProviders(<ContentPanel />);
        expect(container.firstChild).toBeTruthy();
    });
});
