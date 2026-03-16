import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {VarDumperPanel} from './VarDumperPanel';

const makeEntry = (overrides: Partial<{variable: any; line: string}> = {}) => ({
    variable: {foo: 'bar'},
    line: '/src/app.php:10',
    ...overrides,
});

describe('VarDumperPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<VarDumperPanel data={[]} />);
        expect(screen.getByText(/No dumped variables/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<VarDumperPanel data={null as any} />);
        expect(screen.getByText(/No dumped variables/)).toBeInTheDocument();
    });

    it('renders section title with count (singular)', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry()]} />);
        expect(screen.getByText('1 dump')).toBeInTheDocument();
    });

    it('renders section title with count (plural)', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry(), makeEntry({line: '/src/b.php:5'})]} />);
        expect(screen.getByText('2 dumps')).toBeInTheDocument();
    });

    it('renders index badges', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry(), makeEntry({line: '/src/b.php:5'})]} />);
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders file line link', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({line: '/src/Controller.php:42'})]} />);
        expect(screen.getByText('/src/Controller.php:42')).toBeInTheDocument();
    });

    it('renders preview for null variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: null})]} />);
        expect(screen.getByText('null')).toBeInTheDocument();
    });

    it('renders preview for string variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: 'hello world'})]} />);
        expect(screen.getAllByText('"hello world"').length).toBeGreaterThan(0);
    });

    it('renders preview for number variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: 42})]} />);
        expect(screen.getAllByText('42').length).toBeGreaterThan(0);
    });

    it('renders preview for boolean variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: true})]} />);
        expect(screen.getAllByText('true').length).toBeGreaterThan(0);
    });

    it('renders preview for array variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: [1, 2, 3]})]} />);
        expect(screen.getByText('Array(3)')).toBeInTheDocument();
    });

    it('renders preview for object variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: {name: 'John', age: 30}})]} />);
        expect(screen.getByText(/Object \{name, age\}/)).toBeInTheDocument();
    });

    it('expands detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: {key: 'value'}})]} />);
        await user.click(screen.getByText(/Object \{key\}/));
        // JsonRenderer should be visible with expanded content
        expect(screen.getByText('key')).toBeInTheDocument();
    });
});
