import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {ExceptionPanel} from './ExceptionPanel';

const makeException = (overrides: Partial<Parameters<typeof ExceptionPanel>[0]['exceptions'][0]> = {}) => ({
    class: 'RuntimeException',
    message: 'Something went wrong',
    line: '42',
    file: '/src/app.php',
    code: '0',
    trace: [],
    traceAsString: '#0 /src/index.php(10): main()\n#1 {main}',
    ...overrides,
});

describe('ExceptionPanel', () => {
    it('shows empty message when no exceptions', () => {
        renderWithProviders(<ExceptionPanel exceptions={[]} />);
        expect(screen.getByText(/No exceptions found/)).toBeInTheDocument();
    });

    it('handles null exceptions gracefully', () => {
        renderWithProviders(<ExceptionPanel exceptions={null as any} />);
        expect(screen.getByText(/No exceptions found/)).toBeInTheDocument();
    });

    it('renders section title with singular count', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException()]} />);
        expect(screen.getByText('1 exception')).toBeInTheDocument();
    });

    it('renders section title with plural count', () => {
        renderWithProviders(
            <ExceptionPanel exceptions={[makeException(), makeException({class: 'LogicException'})]} />,
        );
        expect(screen.getByText('2 exceptions')).toBeInTheDocument();
    });

    it('renders exception class name in row', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException({class: 'InvalidArgumentException'})]} />);
        expect(screen.getAllByText('InvalidArgumentException').length).toBeGreaterThan(0);
    });

    it('renders exception message in row', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException({message: 'File not found'})]} />);
        // Message appears in both row and detail since always expanded
        expect(screen.getAllByText('File not found').length).toBeGreaterThanOrEqual(1);
    });

    it('renders file location in row', () => {
        renderWithProviders(
            <ExceptionPanel exceptions={[makeException({file: '/var/www/app/Handler.php', line: '99'})]} />,
        );
        expect(screen.getByText(/Handler\.php:99/)).toBeInTheDocument();
    });

    it('renders index badge starting from 1', () => {
        renderWithProviders(
            <ExceptionPanel
                exceptions={[makeException(), makeException({class: 'Error2'}), makeException({class: 'Error3'})]}
            />,
        );
        // Badges may clash with line numbers in code highlight, so use getAllByText
        expect(screen.getAllByText('1').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('2').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('3').length).toBeGreaterThanOrEqual(1);
    });

    it('always shows exception detail (no expand needed)', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException()]} />);
        // Detail is always visible — chips should be immediately present
        expect(screen.getByText('Open Exception Class')).toBeInTheDocument();
        expect(screen.getByText('Open Source Location')).toBeInTheDocument();
    });

    it('shows error code chip when code is not 0', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException({code: '500'})]} />);
        expect(screen.getByText('Code: 500')).toBeInTheDocument();
    });

    it('hides error code chip when code is 0', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException({code: '0'})]} />);
        expect(screen.queryByText('Code: 0')).not.toBeInTheDocument();
    });

    it('renders stack trace by default (open)', () => {
        renderWithProviders(<ExceptionPanel exceptions={[makeException()]} />);
        expect(screen.getByText('Stack Trace')).toBeInTheDocument();
    });
});
