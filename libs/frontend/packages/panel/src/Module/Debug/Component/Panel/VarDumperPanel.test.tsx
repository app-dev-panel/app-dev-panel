import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {VarDumperPanel} from './VarDumperPanel';

const makeEntry = (overrides: Partial<{variable: unknown; line: string}> = {}) => ({
    variable: {foo: 'bar'} as unknown,
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
        renderWithProviders(
            <VarDumperPanel data={[makeEntry({variable: null}), makeEntry({variable: null, line: '/src/b.php:5'})]} />,
        );
        expect(screen.getAllByText('1').length).toBeGreaterThan(0);
        expect(screen.getAllByText('2').length).toBeGreaterThan(0);
    });

    it('renders file line link', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({line: '/src/Controller.php:42'})]} />);
        expect(screen.getByText('/src/Controller.php:42')).toBeInTheDocument();
    });

    it('renders null variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: null})]} />);
        expect(screen.getByText('null')).toBeInTheDocument();
    });

    it('renders string variable with quotes', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: 'hello world'})]} />);
        expect(screen.getByText(/hello world/)).toBeInTheDocument();
    });

    it('renders number variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: 42})]} />);
        expect(screen.getByText('42')).toBeInTheDocument();
    });

    it('renders boolean variable', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: true})]} />);
        expect(screen.getByText('true')).toBeInTheDocument();
    });

    it('renders array variable with count', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: [1, 2, 3]})]} />);
        expect(screen.getAllByText('3').length).toBeGreaterThan(0);
    });

    it('renders associative array keys', () => {
        renderWithProviders(<VarDumperPanel data={[makeEntry({variable: {name: 'John', age: 30}})]} />);
        expect(screen.getByText('name')).toBeInTheDocument();
        expect(screen.getByText('age')).toBeInTheDocument();
    });

    it('renders object with class name', () => {
        renderWithProviders(
            <VarDumperPanel
                data={[makeEntry({variable: {'App\\User#1': {'public $name': 'John', 'private $id': 1}}})]}
            />,
        );
        expect(screen.getByText('App\\User')).toBeInTheDocument();
        expect(screen.getByText(/name/)).toBeInTheDocument();
    });

    it('renders empty array', () => {
        const {container} = renderWithProviders(<VarDumperPanel data={[makeEntry({variable: []})]} />);
        expect(container.textContent).toContain('array');
        expect(container.textContent).toContain('0');
    });
});
