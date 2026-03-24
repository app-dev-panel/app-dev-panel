import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {DataTable} from './Grid';

const columns = [
    {field: 'id', headerName: 'ID', width: 100},
    {field: 'name', headerName: 'Name', width: 200},
];

const rows = [
    {id: 1, name: 'Alice'},
    {id: 2, name: 'Bob'},
    {id: 3, name: 'Charlie'},
];

describe('DataTable', () => {
    it('renders the data grid with rows', () => {
        renderWithProviders(<DataTable rows={rows} columns={columns} />);
        expect(screen.getByText('Alice')).toBeInTheDocument();
        expect(screen.getByText('Bob')).toBeInTheDocument();
        expect(screen.getByText('Charlie')).toBeInTheDocument();
    });

    it('renders column headers', () => {
        renderWithProviders(<DataTable rows={rows} columns={columns} />);
        expect(screen.getByText('ID')).toBeInTheDocument();
        expect(screen.getByText('Name')).toBeInTheDocument();
    });

    it('renders with empty rows', () => {
        renderWithProviders(<DataTable rows={[]} columns={columns} />);
        expect(screen.getByText('No rows')).toBeInTheDocument();
    });

    it('accepts custom getRowId', () => {
        const customRows = [{customId: 'a', name: 'Test'}];
        renderWithProviders(<DataTable rows={customRows} columns={columns} getRowId={(row) => row.customId} />);
        expect(screen.getByText('Test')).toBeInTheDocument();
    });

    it('renders with custom rowsPerPage', () => {
        renderWithProviders(<DataTable rows={rows} columns={columns} rowsPerPage={[5, 10, 25]} />);
        expect(screen.getByText('Alice')).toBeInTheDocument();
    });
});
