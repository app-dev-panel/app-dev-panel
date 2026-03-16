import {HelpOutline} from '@mui/icons-material';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {InfoBox} from './InfoBox';

describe('InfoBox', () => {
    it('renders title', () => {
        renderWithProviders(<InfoBox title="Test Title" severity="info" icon={<HelpOutline />} />);
        expect(screen.getByText('Test Title')).toBeInTheDocument();
    });

    it('renders text when provided', () => {
        renderWithProviders(<InfoBox title="Title" text="Some detail" severity="info" icon={<HelpOutline />} />);
        expect(screen.getByText('Some detail')).toBeInTheDocument();
    });

    it('does not render alert when text is not provided', () => {
        renderWithProviders(<InfoBox title="Title" severity="error" icon={<HelpOutline />} />);
        expect(screen.getByText('Title')).toBeInTheDocument();
    });

    it('renders React element as text', () => {
        renderWithProviders(
            <InfoBox
                title="Title"
                text={<span data-testid="custom">Custom</span>}
                severity="info"
                icon={<HelpOutline />}
            />,
        );
        expect(screen.getByTestId('custom')).toBeInTheDocument();
    });
});
