import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {ServicesPanel} from './ServicesPanel';

describe('ServicesPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<ServicesPanel data={[]} />);
        expect(screen.getByText(/No spied services/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<ServicesPanel data={null as any} />);
        expect(screen.getByText(/No spied services/)).toBeInTheDocument();
    });

    it('renders tabs with service data', () => {
        const data = [
            {
                service: 'App\\Service',
                class: 'App\\Service',
                method: 'handle',
                arguments: ['arg1'],
                result: 'ok',
                status: 'success' as const,
                error: null,
                timeStart: 0.001,
                timeEnd: 0.005,
            },
        ];
        renderWithProviders(<ServicesPanel data={data} />);
        expect(screen.getByText('Summary')).toBeInTheDocument();
        expect(screen.getByText('All')).toBeInTheDocument();
    });
});
