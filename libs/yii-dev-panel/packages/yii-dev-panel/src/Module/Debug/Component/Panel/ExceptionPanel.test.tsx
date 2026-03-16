import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {ExceptionPanel} from './ExceptionPanel';

describe('ExceptionPanel', () => {
    it('renders cascade exceptions text with count', () => {
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
        expect(screen.getByText(/cascade exceptions/)).toBeInTheDocument();
        // The bold element contains the count
        const bold = screen.getByText(/cascade exceptions/).querySelector('b');
        expect(bold?.textContent).toBe('1');
    });

    it('handles empty exceptions', () => {
        renderWithProviders(<ExceptionPanel exceptions={[]} />);
        const bold = screen.getByText(/cascade exceptions/).querySelector('b');
        expect(bold?.textContent).toBe('0');
    });

    it('handles null exceptions gracefully', () => {
        renderWithProviders(<ExceptionPanel exceptions={null as any} />);
        expect(screen.getByText(/cascade exceptions/)).toBeInTheDocument();
    });
});
