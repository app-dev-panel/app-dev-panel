import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {VarDumperPanel} from './VarDumperPanel';

describe('VarDumperPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<VarDumperPanel data={[]} />);
        expect(screen.getByText(/No dumped variables/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<VarDumperPanel data={null as any} />);
        expect(screen.getByText(/No dumped variables/)).toBeInTheDocument();
    });

    it('renders dump entries', () => {
        const data = [{variable: {foo: 'bar'}, line: '/src/app.php:10'}];
        renderWithProviders(<VarDumperPanel data={data} />);
        expect(screen.getByText('/src/app.php:10')).toBeInTheDocument();
    });
});
