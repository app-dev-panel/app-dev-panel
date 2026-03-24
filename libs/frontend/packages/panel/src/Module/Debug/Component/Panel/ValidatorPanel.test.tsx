import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {ValidatorPanel} from './ValidatorPanel';

type Validation = {value: any; rules: any; result: boolean; errors: any};

const makeValidation = (overrides: Partial<Validation> = {}): Validation => ({
    value: 'test@example.com',
    rules: {type: 'email'},
    result: true,
    errors: [],
    ...overrides,
});

describe('ValidatorPanel', () => {
    it('shows empty state when data is empty array', () => {
        renderWithProviders(<ValidatorPanel data={[]} />);
        expect(screen.getByText('No validations found')).toBeInTheDocument();
    });

    it('shows empty state when data is null', () => {
        renderWithProviders(<ValidatorPanel data={null as any} />);
        expect(screen.getByText('No validations found')).toBeInTheDocument();
    });

    it('renders validation count in section title', () => {
        const data = [makeValidation(), makeValidation({value: 'abc', result: false, errors: {name: ['required']}})];
        renderWithProviders(<ValidatorPanel data={data} />);
        expect(screen.getByText('2 validations')).toBeInTheDocument();
    });

    it('renders valid and invalid count chips', () => {
        const data = [
            makeValidation({result: true}),
            makeValidation({result: true}),
            makeValidation({result: false, errors: {field: ['error']}}),
        ];
        renderWithProviders(<ValidatorPanel data={data} />);
        expect(screen.getByText('2 valid')).toBeInTheDocument();
        expect(screen.getByText('1 invalid')).toBeInTheDocument();
    });

    it('renders VALID chip for passing validations', () => {
        renderWithProviders(<ValidatorPanel data={[makeValidation({result: true})]} />);
        expect(screen.getByText('VALID')).toBeInTheDocument();
    });

    it('renders INVALID chip for failing validations', () => {
        renderWithProviders(
            <ValidatorPanel data={[makeValidation({result: false, errors: {name: ['is required']}})]} />,
        );
        expect(screen.getByText('INVALID')).toBeInTheDocument();
    });

    it('renders error count chip for validations with errors', () => {
        const data = [makeValidation({result: false, errors: {name: ['required'], email: ['invalid format']}})];
        renderWithProviders(<ValidatorPanel data={data} />);
        expect(screen.getByText('2 errors')).toBeInTheDocument();
    });

    it('renders singular error chip when only one error', () => {
        const data = [makeValidation({result: false, errors: {name: ['required']}})];
        renderWithProviders(<ValidatorPanel data={data} />);
        expect(screen.getByText('1 error')).toBeInTheDocument();
    });

    it('filters validations by value', async () => {
        const user = userEvent.setup();
        const data = [
            makeValidation({value: 'john@example.com', result: true}),
            makeValidation({value: 'invalid-email', result: false, errors: {email: ['invalid']}}),
        ];
        renderWithProviders(<ValidatorPanel data={data} />);
        const input = screen.getByPlaceholderText('Filter validations...');
        await user.type(input, 'john');
        expect(screen.getByText('1 validations')).toBeInTheDocument();
        expect(screen.queryByText('INVALID')).not.toBeInTheDocument();
    });

    it('filters validations by status keyword', async () => {
        const user = userEvent.setup();
        const data = [
            makeValidation({value: 'good', result: true}),
            makeValidation({value: 'bad', result: false, errors: {f: ['err']}}),
        ];
        renderWithProviders(<ValidatorPanel data={data} />);
        const input = screen.getByPlaceholderText('Filter validations...');
        await user.type(input, 'invalid');
        expect(screen.getByText('1 validations')).toBeInTheDocument();
    });

    it('expands validation row on click to show details', async () => {
        const user = userEvent.setup();
        const data = [makeValidation({value: 'test-value', rules: {type: 'string'}, result: true})];
        renderWithProviders(<ValidatorPanel data={data} />);
        await user.click(screen.getByText('test-value'));
        expect(screen.getByText('Value')).toBeInTheDocument();
        expect(screen.getByText('Rules')).toBeInTheDocument();
    });

    it('shows error table when expanding invalid validation with errors', async () => {
        const user = userEvent.setup();
        const data = [makeValidation({value: 'x', result: false, errors: {username: ['too short']}})];
        renderWithProviders(<ValidatorPanel data={data} />);
        await user.click(screen.getByText('INVALID'));
        expect(screen.getByText('Errors')).toBeInTheDocument();
        expect(screen.getByText('username')).toBeInTheDocument();
        expect(screen.getByText('too short')).toBeInTheDocument();
    });
});
