import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {VarDumpValue} from './VarDumpValue';

describe('VarDumpValue', () => {
    describe('scalars', () => {
        it('renders null', () => {
            renderWithProviders(<VarDumpValue value={null} />);
            expect(screen.getByText('null')).toBeInTheDocument();
        });

        it('renders undefined as null', () => {
            renderWithProviders(<VarDumpValue value={undefined} />);
            expect(screen.getByText('null')).toBeInTheDocument();
        });

        it('renders true', () => {
            renderWithProviders(<VarDumpValue value={true} />);
            expect(screen.getByText('true')).toBeInTheDocument();
        });

        it('renders false', () => {
            renderWithProviders(<VarDumpValue value={false} />);
            expect(screen.getByText('false')).toBeInTheDocument();
        });

        it('renders integer with type annotation', () => {
            renderWithProviders(<VarDumpValue value={42} />);
            expect(screen.getByText('42')).toBeInTheDocument();
            expect(screen.getByText('int')).toBeInTheDocument();
        });

        it('renders float with type annotation', () => {
            renderWithProviders(<VarDumpValue value={3.14} />);
            expect(screen.getByText('3.14')).toBeInTheDocument();
            expect(screen.getByText('float')).toBeInTheDocument();
        });

        it('renders string with quotes and length', () => {
            renderWithProviders(<VarDumpValue value="hello" />);
            expect(screen.getByText(/hello/)).toBeInTheDocument();
            expect(screen.getByText('(5)')).toBeInTheDocument();
        });

        it('renders empty string', () => {
            renderWithProviders(<VarDumpValue value="" />);
            expect(screen.getByText('(0)')).toBeInTheDocument();
        });
    });

    describe('arrays', () => {
        it('renders empty array', () => {
            renderWithProviders(<VarDumpValue value={[]} />);
            expect(screen.getByText(/0/)).toBeInTheDocument();
        });

        it('renders indexed array with items', () => {
            renderWithProviders(<VarDumpValue value={[1, 2, 3]} />);
            expect(screen.getAllByText('3').length).toBeGreaterThan(0);
        });

        it('renders associative array', () => {
            renderWithProviders(<VarDumpValue value={{foo: 'bar', baz: 42}} />);
            expect(screen.getByText('foo')).toBeInTheDocument();
            expect(screen.getByText('baz')).toBeInTheDocument();
        });
    });

    describe('objects', () => {
        it('renders object with class name', () => {
            renderWithProviders(
                <VarDumpValue value={{'App\\Model\\User#5': {'public $name': 'John', 'private $id': 1}}} />,
            );
            expect(screen.getByText('App\\Model\\User')).toBeInTheDocument();
            expect(screen.getByText('#5')).toBeInTheDocument();
        });

        it('renders property visibility keywords', () => {
            renderWithProviders(
                <VarDumpValue
                    value={{'App\\Foo#1': {'public $name': 'John', 'private $secret': 'hidden', 'protected $data': []}}}
                />,
            );
            expect(screen.getAllByText('public').length).toBeGreaterThan(0);
            expect(screen.getAllByText('private').length).toBeGreaterThan(0);
            expect(screen.getAllByText('protected').length).toBeGreaterThan(0);
        });

        it('renders stateless object', () => {
            renderWithProviders(<VarDumpValue value={{'App\\EmptyService#1': '{stateless object}'}} />);
            expect(screen.getByText('App\\EmptyService')).toBeInTheDocument();
        });
    });

    describe('special strings', () => {
        it('renders object reference', () => {
            const {container} = renderWithProviders(<VarDumpValue value="object@App\User#5" />);
            expect(container.textContent).toContain('User#5');
            expect(container.textContent).toContain('{...}');
        });

        it('renders truncated array', () => {
            const {container} = renderWithProviders(<VarDumpValue value="array (5 items) [...]" />);
            expect(container.textContent).toContain('array');
            expect(container.textContent).toContain('5');
        });

        it('renders truncated object', () => {
            const {container} = renderWithProviders(<VarDumpValue value="App\User#5 (...)" />);
            expect(container.textContent).toContain('User#5');
            expect(container.textContent).toContain('{...}');
        });

        it('renders closed resource', () => {
            renderWithProviders(<VarDumpValue value="{closed resource}" />);
            expect(screen.getByText('{closed resource}')).toBeInTheDocument();
        });

        it('renders resource with type', () => {
            renderWithProviders(<VarDumpValue value="{stream resource}" />);
            expect(screen.getByText('{stream resource}')).toBeInTheDocument();
        });
    });

    describe('collapsible behavior', () => {
        it('collapses nested arrays beyond depth 2', () => {
            const deepValue = {level1: {level2: {level3: 'deep'}}};
            renderWithProviders(<VarDumpValue value={deepValue} />);
            // level1 and level2 should be visible (auto-expanded for depth 0 and 1)
            expect(screen.getByText('level1')).toBeInTheDocument();
            expect(screen.getByText('level2')).toBeInTheDocument();
        });

        it('toggles array collapse on arrow click', async () => {
            const user = userEvent.setup();
            renderWithProviders(<VarDumpValue value={{key: 'value'}} defaultExpanded={false} />);
            // Find the toggle arrow and click it
            const arrow = screen.getByRole('button');
            expect(arrow).toBeInTheDocument();
            await user.click(arrow);
            expect(screen.getByText('key')).toBeInTheDocument();
        });
    });
});
