import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {ExplainPlanVisualizer} from './ExplainPlanVisualizer';

describe('ExplainPlanVisualizer', () => {
    describe('PostgreSQL JSON format', () => {
        const pgData = [
            {
                Plan: {
                    'Node Type': 'Seq Scan',
                    'Relation Name': 'users',
                    Alias: 'users',
                    'Startup Cost': 0.0,
                    'Total Cost': 10.88,
                    'Plan Rows': 100,
                    'Plan Width': 36,
                    'Actual Startup Time': 0.012,
                    'Actual Total Time': 0.45,
                    'Actual Rows': 100,
                    'Actual Loops': 1,
                    Filter: '(active = true)',
                    'Rows Removed by Filter': 50,
                },
                'Planning Time': 0.05,
                'Execution Time': 0.5,
            },
        ];

        it('renders PostgreSQL plan node type', () => {
            renderWithProviders(<ExplainPlanVisualizer data={pgData} />);
            expect(screen.getByText('Seq Scan')).toBeInTheDocument();
        });

        it('renders table name chip', () => {
            renderWithProviders(<ExplainPlanVisualizer data={pgData} />);
            expect(screen.getByText('users')).toBeInTheDocument();
        });

        it('renders execution time in summary', () => {
            renderWithProviders(<ExplainPlanVisualizer data={pgData} />);
            expect(screen.getByText('0.500 ms')).toBeInTheDocument();
        });

        it('renders planning time in summary', () => {
            renderWithProviders(<ExplainPlanVisualizer data={pgData} />);
            expect(screen.getByText('0.050 ms')).toBeInTheDocument();
        });

        it('renders nested plans', () => {
            const nested = [
                {
                    Plan: {
                        'Node Type': 'Hash Join',
                        'Total Cost': 100,
                        'Plan Rows': 50,
                        'Actual Total Time': 5.0,
                        'Actual Rows': 50,
                        'Actual Loops': 1,
                        Plans: [
                            {
                                'Node Type': 'Index Scan',
                                'Relation Name': 'orders',
                                'Index Name': 'orders_pkey',
                                'Total Cost': 10,
                                'Plan Rows': 10,
                                'Actual Total Time': 1.0,
                                'Actual Rows': 10,
                                'Actual Loops': 1,
                            },
                            {
                                'Node Type': 'Seq Scan',
                                'Relation Name': 'products',
                                'Total Cost': 50,
                                'Plan Rows': 200,
                                'Actual Total Time': 3.0,
                                'Actual Rows': 200,
                                'Actual Loops': 1,
                            },
                        ],
                    },
                },
            ];
            renderWithProviders(<ExplainPlanVisualizer data={nested} />);
            expect(screen.getByText('Hash Join')).toBeInTheDocument();
            expect(screen.getByText('Index Scan')).toBeInTheDocument();
            expect(screen.getByText('Seq Scan')).toBeInTheDocument();
            expect(screen.getByText('orders')).toBeInTheDocument();
            expect(screen.getByText('products')).toBeInTheDocument();
        });
    });

    describe('MySQL format', () => {
        const mysqlData = [
            {
                id: 1,
                select_type: 'SIMPLE',
                table: 'users',
                type: 'ALL',
                possible_keys: null,
                key: null,
                key_len: null,
                ref: null,
                rows: 100,
                filtered: 50.0,
                Extra: 'Using where',
            },
        ];

        it('renders MySQL table name', () => {
            renderWithProviders(<ExplainPlanVisualizer data={mysqlData} />);
            expect(screen.getByText('users')).toBeInTheDocument();
        });

        it('renders access type chip', () => {
            renderWithProviders(<ExplainPlanVisualizer data={mysqlData} />);
            expect(screen.getByText('ALL')).toBeInTheDocument();
        });

        it('renders Extra info', () => {
            renderWithProviders(<ExplainPlanVisualizer data={mysqlData} />);
            expect(screen.getByText('Using where')).toBeInTheDocument();
        });

        it('renders filtered percentage', () => {
            renderWithProviders(<ExplainPlanVisualizer data={mysqlData} />);
            expect(screen.getByText('50%')).toBeInTheDocument();
        });
    });

    describe('Detail/text format', () => {
        const detailData = [{detail: 'SCAN users'}, {detail: 'SEARCH orders USING INDEX orders_user_id'}];

        it('renders detail text', () => {
            renderWithProviders(<ExplainPlanVisualizer data={detailData} />);
            expect(screen.getByText('SCAN users')).toBeInTheDocument();
        });

        it('renders all detail rows', () => {
            renderWithProviders(<ExplainPlanVisualizer data={detailData} />);
            expect(screen.getByText(/SEARCH orders/)).toBeInTheDocument();
        });
    });

    describe('empty and unknown data', () => {
        it('returns null for empty array', () => {
            const {container} = renderWithProviders(<ExplainPlanVisualizer data={[]} />);
            expect(container.firstChild).toBeNull();
        });

        it('returns null for unknown format', () => {
            const {container} = renderWithProviders(<ExplainPlanVisualizer data={[{foo: 'bar'}]} />);
            expect(container.firstChild).toBeNull();
        });
    });
});
