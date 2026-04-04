import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {AuthorizationPanel} from './AuthorizationPanel';

type VoterEntry = string | {voter: string; result: string};
type AccessDecision = {
    attribute: string;
    subject: string;
    result: string;
    voters: VoterEntry[];
    duration?: number | null;
    context?: Record<string, unknown>;
};
type AuthenticationEvent = {
    type: string;
    provider: string;
    result: string;
    time: number;
    details: Record<string, unknown>;
};
type TokenInfo = {type: string; attributes: Record<string, unknown>; expiresAt: string | null};
type ImpersonationInfo = {originalUser: string; impersonatedUser: string};
type AuthorizationData = {
    username: string | null;
    roles: string[];
    effectiveRoles?: string[];
    firewallName: string | null;
    authenticated: boolean;
    token?: TokenInfo | null;
    impersonation?: ImpersonationInfo | null;
    authenticationEvents?: AuthenticationEvent[];
    accessDecisions: AccessDecision[];
};

const makeAuthorizationData = (overrides: Partial<AuthorizationData> = {}): AuthorizationData => ({
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

describe('AuthorizationPanel', () => {
    it('shows empty state when data is null', () => {
        renderWithProviders(<AuthorizationPanel data={null as any} />);
        expect(screen.getByText('No authorization data found')).toBeInTheDocument();
    });

    it('renders username', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({username: 'john'})} />);
        expect(screen.getByText('john')).toBeInTheDocument();
    });

    it('renders Anonymous when username is null', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({username: null})} />);
        expect(screen.getByText('Anonymous')).toBeInTheDocument();
    });

    it('renders Authenticated chip when authenticated', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({authenticated: true})} />);
        expect(screen.getByText('Authenticated')).toBeInTheDocument();
    });

    it('renders Not Authenticated chip when not authenticated', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({authenticated: false})} />);
        expect(screen.getByText('Not Authenticated')).toBeInTheDocument();
    });

    it('renders firewall name when present', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({firewallName: 'api_firewall'})} />);
        expect(screen.getByText('api_firewall')).toBeInTheDocument();
    });

    it('does not render firewall row when firewallName is null', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({firewallName: null})} />);
        expect(screen.queryByText('Firewall')).not.toBeInTheDocument();
    });

    it('renders role chips', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({roles: ['ROLE_ADMIN', 'ROLE_USER']})} />);
        expect(screen.getByText('ROLE_ADMIN')).toBeInTheDocument();
        expect(screen.getByText('ROLE_USER')).toBeInTheDocument();
    });

    it('does not render roles row when roles array is empty', () => {
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({roles: []})} />);
        expect(screen.queryByText('Roles')).not.toBeInTheDocument();
    });

    it('renders access decisions section title with counts', () => {
        const decisions = [
            makeDecision({result: 'ACCESS_GRANTED'}),
            makeDecision({result: 'ACCESS_DENIED', attribute: 'EDIT'}),
            makeDecision({result: 'ACCESS_GRANTED', attribute: 'VIEW'}),
        ];
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({accessDecisions: decisions})} />);
        expect(screen.getByText('3 access decisions (2 granted, 1 denied)')).toBeInTheDocument();
    });

    it('renders decision result chips', () => {
        const decisions = [
            makeDecision({result: 'ACCESS_GRANTED'}),
            makeDecision({result: 'ACCESS_DENIED', attribute: 'DELETE'}),
        ];
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({accessDecisions: decisions})} />);
        expect(screen.getByText('ACCESS_GRANTED')).toBeInTheDocument();
        expect(screen.getByText('ACCESS_DENIED')).toBeInTheDocument();
    });

    it('expands decision row on click to show voters', async () => {
        const user = userEvent.setup();
        const decisions = [makeDecision({attribute: 'CAN_EDIT', voters: ['RoleVoter', 'SecurityVoter']})];
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({accessDecisions: decisions})} />);
        await user.click(screen.getByText('CAN_EDIT'));
        expect(screen.getByText('Voters')).toBeInTheDocument();
        expect(screen.getByText('RoleVoter')).toBeInTheDocument();
        expect(screen.getByText('SecurityVoter')).toBeInTheDocument();
    });

    it('expands decision row on click to show object voters', async () => {
        const user = userEvent.setup();
        const decisions = [
            makeDecision({
                attribute: 'CAN_DELETE',
                voters: [
                    {voter: 'RoleVoter', result: 'ACCESS_GRANTED'},
                    {voter: 'PostVoter', result: 'ACCESS_DENIED'},
                ],
            }),
        ];
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({accessDecisions: decisions})} />);
        await user.click(screen.getByText('CAN_DELETE'));
        expect(screen.getByText('Voters')).toBeInTheDocument();
        expect(screen.getByText('RoleVoter: ACCESS_GRANTED')).toBeInTheDocument();
        expect(screen.getByText('PostVoter: ACCESS_DENIED')).toBeInTheDocument();
    });

    it('renders impersonation banner', () => {
        renderWithProviders(
            <AuthorizationPanel
                data={makeAuthorizationData({
                    impersonation: {originalUser: 'admin@example.com', impersonatedUser: 'user@example.com'},
                })}
            />,
        );
        expect(screen.getByText(/Impersonating user@example.com/)).toBeInTheDocument();
        expect(screen.getByText(/original: admin@example.com/)).toBeInTheDocument();
    });

    it('renders token info', () => {
        renderWithProviders(
            <AuthorizationPanel
                data={makeAuthorizationData({token: {type: 'jwt', attributes: {sub: '123'}, expiresAt: '2026-12-31'}})}
            />,
        );
        expect(screen.getByText('JWT')).toBeInTheDocument();
        expect(screen.getByText('expires 2026-12-31')).toBeInTheDocument();
    });

    it('renders effective roles when different from assigned', () => {
        renderWithProviders(
            <AuthorizationPanel
                data={makeAuthorizationData({
                    roles: ['ROLE_ADMIN'],
                    effectiveRoles: ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR'],
                })}
            />,
        );
        expect(screen.getByText('ROLE_USER')).toBeInTheDocument();
        expect(screen.getByText('ROLE_EDITOR')).toBeInTheDocument();
    });

    it('renders authentication events', () => {
        renderWithProviders(
            <AuthorizationPanel
                data={makeAuthorizationData({
                    authenticationEvents: [
                        {type: 'login', provider: 'form_login', result: 'success', time: 1234567890, details: {}},
                        {type: 'failure', provider: 'api_key', result: 'failure', time: 1234567891, details: {}},
                    ],
                })}
            />,
        );
        expect(screen.getByText('Authentication Events (2)')).toBeInTheDocument();
        expect(screen.getByText('login')).toBeInTheDocument();
        expect(screen.getByText('form_login')).toBeInTheDocument();
    });

    it('renders decision duration when present', async () => {
        const _user = userEvent.setup();
        const decisions = [makeDecision({attribute: 'EDIT', duration: 0.0023})];
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({accessDecisions: decisions})} />);
        expect(screen.getByText('2.3ms')).toBeInTheDocument();
    });

    it('renders decision context when expanded', async () => {
        const user = userEvent.setup();
        const decisions = [makeDecision({attribute: 'EDIT_POST', context: {route: '/admin/posts'}})];
        renderWithProviders(<AuthorizationPanel data={makeAuthorizationData({accessDecisions: decisions})} />);
        await user.click(screen.getByText('EDIT_POST'));
        expect(screen.getByText('Context')).toBeInTheDocument();
        expect(screen.getByText('route: /admin/posts')).toBeInTheDocument();
    });
});
