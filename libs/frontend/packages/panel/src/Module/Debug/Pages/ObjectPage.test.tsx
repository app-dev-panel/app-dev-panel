import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {ObjectPage} from './ObjectPage';

describe('ObjectPage', () => {
    it('shows missing parameters message when no query params', () => {
        renderWithProviders(<ObjectPage />, {route: '/debug/object'});
        expect(screen.getByText('Missing parameters')).toBeInTheDocument();
    });

    it('shows missing parameters when only debugEntry provided', () => {
        renderWithProviders(<ObjectPage />, {route: '/debug/object?debugEntry=abc123'});
        expect(screen.getByText('Missing parameters')).toBeInTheDocument();
    });

    it('shows missing parameters when only id provided', () => {
        renderWithProviders(<ObjectPage />, {route: '/debug/object?id=42'});
        expect(screen.getByText('Missing parameters')).toBeInTheDocument();
    });

    it('shows error state when API returns no data', async () => {
        renderWithProviders(<ObjectPage />, {route: '/debug/object?debugEntry=test-123&id=42'});
        expect(await screen.findByText('Object not found')).toBeInTheDocument();
    });
});
