import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {ServicesPanel} from './ServicesPanel';

const makeService = (overrides: Partial<Parameters<typeof ServicesPanel>[0]['data'][0]> = {}) => ({
    service: 'App\\Service\\UserService',
    class: 'App\\Service\\UserService',
    method: 'findById',
    arguments: [{id: 1}],
    result: {name: 'John'},
    status: 'success' as const,
    error: null,
    timeStart: 0.001,
    timeEnd: 0.005,
    ...overrides,
});

describe('ServicesPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<ServicesPanel data={[]} />);
        expect(screen.getByText(/No spied services/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<ServicesPanel data={null as any} />);
        expect(screen.getByText(/No spied services/)).toBeInTheDocument();
    });

    it('renders Summary and All tabs', () => {
        renderWithProviders(<ServicesPanel data={[makeService()]} />);
        expect(screen.getByText('Summary')).toBeInTheDocument();
        expect(screen.getByText('All')).toBeInTheDocument();
    });

    it('renders call count in summary', () => {
        renderWithProviders(<ServicesPanel data={[makeService(), makeService()]} />);
        expect(screen.getByText('2 calls')).toBeInTheDocument();
    });

    it('renders error badge in summary when errors exist', () => {
        renderWithProviders(<ServicesPanel data={[makeService({status: 'error', error: 'Connection failed'})]} />);
        expect(screen.getByText('1 err')).toBeInTheDocument();
    });

    it('switches to All tab', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ServicesPanel data={[makeService()]} />);
        await user.click(screen.getByText('All'));
        expect(screen.getByText('OK')).toBeInTheDocument();
    });

    it('shows ERROR badge for failed calls in All view', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ServicesPanel data={[makeService({status: 'error', error: 'Timeout'})]} />);
        await user.click(screen.getByText('All'));
        expect(screen.getByText('ERROR')).toBeInTheDocument();
    });

    it('expands detail in All view on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ServicesPanel data={[makeService()]} />);
        await user.click(screen.getByText('All'));
        // Click the method row
        const methodTexts = screen.getAllByText(/UserService::findById/);
        await user.click(methodTexts[0]);
        expect(screen.getByText('Arguments')).toBeInTheDocument();
        expect(screen.getByText('Result')).toBeInTheDocument();
    });

    it('shows filter in All view', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ServicesPanel data={[makeService()]} />);
        await user.click(screen.getByText('All'));
        expect(screen.getByPlaceholderText('Filter services...')).toBeInTheDocument();
    });
});
