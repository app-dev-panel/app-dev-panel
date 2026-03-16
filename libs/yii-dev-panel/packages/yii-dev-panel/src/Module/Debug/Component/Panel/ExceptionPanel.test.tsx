import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {ExceptionPanel} from './ExceptionPanel';

describe('ExceptionPanel', () => {
    it('shows empty message when no exceptions', () => {
        renderWithProviders(<ExceptionPanel exceptions={[]} />);
        expect(screen.getByText(/No exceptions found/)).toBeInTheDocument();
    });

    it('handles null exceptions gracefully', () => {
        renderWithProviders(<ExceptionPanel exceptions={null as any} />);
        expect(screen.getByText(/No exceptions found/)).toBeInTheDocument();
    });

    it('renders exception rows with class and message', () => {
        const exceptions = [
            {
                class: 'RuntimeException',
                message: 'Something failed',
                line: '42',
                file: '/src/app.php',
                code: '0',
                trace: [],
                traceAsString: '',
            },
        ];
        renderWithProviders(<ExceptionPanel exceptions={exceptions} />);
        expect(screen.getAllByText('RuntimeException').length).toBeGreaterThan(0);
        expect(screen.getByText('Something failed')).toBeInTheDocument();
    });

    it('renders section title with count', () => {
        const exceptions = [
            {class: 'Error1', message: 'msg1', line: '1', file: '/a.php', code: '0', trace: [], traceAsString: ''},
            {class: 'Error2', message: 'msg2', line: '2', file: '/b.php', code: '0', trace: [], traceAsString: ''},
        ];
        renderWithProviders(<ExceptionPanel exceptions={exceptions} />);
        expect(screen.getByText('2 exceptions')).toBeInTheDocument();
    });
});
