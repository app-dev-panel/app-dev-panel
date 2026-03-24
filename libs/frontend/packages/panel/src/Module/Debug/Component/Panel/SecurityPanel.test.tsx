import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {SecurityPanel} from './SecurityPanel';

type AccessDecision = {attribute: string; subject: string; result: string; voters: string[]};
type SecurityData = {
    username: string | null;
    roles: string[];
    firewallName: string | null;
    authenticated: boolean;
    accessDecisions: AccessDecision[];
};

const makeSecurityData = (overrides: Partial<SecurityData> = {}): SecurityData => ({
    username: 'admin',
    roles: ['ROLE_ADMIN'],
    firewallName: 'main',
    authenticated: true,
    accessDecisions: [],
    ...overrides,
});

const makeDecision = (overrides: Partial<AccessDecision> = {}): AccessDecision => ({
    attribute: 'ROLE_ADMIN',
    subject: 'App\\Entity\\User',
    result: 'GRANTED',
    voters: ['RoleVoter'],
    ...overrides,
});

describe('SecurityPanel', () => {
    it('shows empty state when data is null', () => {
        renderWithProviders(<SecurityPanel data={null as any} />);
        expect(screen.getByText('No security data found')).toBeInTheDocument();
    });

    it('renders username', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({username: 'john'})} />);
        expect(screen.getByText('john')).toBeInTheDocument();
    });

    it('renders Anonymous when username is null', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({username: null})} />);
        expect(screen.getByText('Anonymous')).toBeInTheDocument();
    });

    it('renders Authenticated chip when authenticated', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({authenticated: true})} />);
        expect(screen.getByText('Authenticated')).toBeInTheDocument();
    });

    it('renders Not Authenticated chip when not authenticated', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({authenticated: false})} />);
        expect(screen.getByText('Not Authenticated')).toBeInTheDocument();
    });

    it('renders firewall name when present', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({firewallName: 'api_firewall'})} />);
        expect(screen.getByText('api_firewall')).toBeInTheDocument();
    });

    it('does not render firewall row when firewallName is null', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({firewallName: null})} />);
        expect(screen.queryByText('Firewall')).not.toBeInTheDocument();
    });

    it('renders role chips', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({roles: ['ROLE_ADMIN', 'ROLE_USER']})} />);
        expect(screen.getByText('ROLE_ADMIN')).toBeInTheDocument();
        expect(screen.getByText('ROLE_USER')).toBeInTheDocument();
    });

    it('does not render roles row when roles array is empty', () => {
        renderWithProviders(<SecurityPanel data={makeSecurityData({roles: []})} />);
        expect(screen.queryByText('Roles')).not.toBeInTheDocument();
    });

    it('renders access decisions section title with counts', () => {
        const decisions = [
            makeDecision({result: 'GRANTED'}),
            makeDecision({result: 'DENIED', attribute: 'EDIT'}),
            makeDecision({result: 'GRANTED', attribute: 'VIEW'}),
        ];
        renderWithProviders(<SecurityPanel data={makeSecurityData({accessDecisions: decisions})} />);
        expect(screen.getByText('3 access decisions (2 granted, 1 denied)')).toBeInTheDocument();
    });

    it('renders decision result chips', () => {
        const decisions = [makeDecision({result: 'GRANTED'}), makeDecision({result: 'DENIED', attribute: 'DELETE'})];
        renderWithProviders(<SecurityPanel data={makeSecurityData({accessDecisions: decisions})} />);
        expect(screen.getByText('GRANTED')).toBeInTheDocument();
        expect(screen.getByText('DENIED')).toBeInTheDocument();
    });

    it('expands decision row on click to show voters', async () => {
        const user = userEvent.setup();
        const decisions = [makeDecision({attribute: 'CAN_EDIT', voters: ['RoleVoter', 'SecurityVoter']})];
        renderWithProviders(<SecurityPanel data={makeSecurityData({accessDecisions: decisions})} />);
        await user.click(screen.getByText('CAN_EDIT'));
        expect(screen.getByText('Voters')).toBeInTheDocument();
        expect(screen.getByText('RoleVoter')).toBeInTheDocument();
        expect(screen.getByText('SecurityVoter')).toBeInTheDocument();
    });
});
