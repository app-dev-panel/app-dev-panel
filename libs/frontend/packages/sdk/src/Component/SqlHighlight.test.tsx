import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {SqlHighlight} from './SqlHighlight';

describe('SqlHighlight', () => {
    it('renders SQL text', () => {
        renderWithProviders(<SqlHighlight sql="SELECT * FROM users" />);
        expect(screen.getByText(/SELECT/)).toBeInTheDocument();
        expect(screen.getByText(/users/)).toBeInTheDocument();
    });

    it('renders inline mode without format toggle', () => {
        renderWithProviders(<SqlHighlight sql="SELECT id FROM users WHERE id = 1" inline />);
        expect(screen.getByText(/SELECT/)).toBeInTheDocument();
        expect(screen.queryByLabelText(/Format SQL/i)).not.toBeInTheDocument();
    });

    it('formats SQL when formatted prop is true', () => {
        renderWithProviders(<SqlHighlight sql="SELECT id, name FROM users WHERE active = 1 ORDER BY name" formatted />);
        // Formatted SQL should contain uppercase keywords
        expect(screen.getByText(/SELECT/)).toBeInTheDocument();
        expect(screen.getByText(/FROM/)).toBeInTheDocument();
        expect(screen.getByText(/WHERE/)).toBeInTheDocument();
    });

    it('shows toggle button for multi-line SQL', () => {
        renderWithProviders(
            <SqlHighlight sql="SELECT id, name FROM users WHERE active = 1 ORDER BY name" formatted allowToggle />,
        );
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('handles malformed SQL gracefully', () => {
        renderWithProviders(<SqlHighlight sql="NOT VALID SQL $$$ %%%" />);
        expect(screen.getByText(/NOT/)).toBeInTheDocument();
    });
});
