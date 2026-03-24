import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {DebugChip} from './DebugChip';

describe('DebugChip', () => {
    it('renders label text', () => {
        renderWithProviders(<DebugChip label="info" />);
        expect(screen.getByText('info')).toBeInTheDocument();
    });

    it('renders numeric label', () => {
        renderWithProviders(<DebugChip label={42} />);
        expect(screen.getByText('42')).toBeInTheDocument();
    });

    it('renders with color prop', () => {
        renderWithProviders(<DebugChip label="error" color="error" />);
        expect(screen.getByText('error')).toBeInTheDocument();
    });

    it('renders without label', () => {
        const {container} = renderWithProviders(<DebugChip />);
        expect(container.querySelector('.MuiChip-root')).toBeInTheDocument();
    });
});
