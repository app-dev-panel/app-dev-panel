import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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
        expect(screen.getByText('File not found')).toBeInTheDocument();
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
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('expands exception detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ExceptionPanel exceptions={[makeException({message: 'Click me'})]} />);
        await user.click(screen.getByText('Click me'));
        // After expanding, should see the "Open Exception Class" and "Open Source Location" chips
        expect(screen.getByText('Open Exception Class')).toBeInTheDocument();
        expect(screen.getByText('Open Source Location')).toBeInTheDocument();
    });

    it('can expand and collapse without errors', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ExceptionPanel exceptions={[makeException({message: 'Toggle me'})]} />);
        await user.click(screen.getAllByText('Toggle me')[0]);
        expect(screen.getByText('Open Exception Class')).toBeInTheDocument();
        // Second click triggers collapse — no assertion on DOM removal due to jsdom transition timing
        await user.click(screen.getAllByText('Toggle me')[0]);
    });

    it('shows error code chip when code is not 0', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ExceptionPanel exceptions={[makeException({code: '500', message: 'expand me'})]} />);
        await user.click(screen.getByText('expand me'));
        expect(screen.getByText('Code: 500')).toBeInTheDocument();
    });

    it('hides error code chip when code is 0', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ExceptionPanel exceptions={[makeException({code: '0', message: 'expand me 2'})]} />);
        await user.click(screen.getByText('expand me 2'));
        expect(screen.queryByText('Code: 0')).not.toBeInTheDocument();
    });
});
