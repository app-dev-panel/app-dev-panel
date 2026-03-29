import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useState} from 'react';

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
type GuardInfo = {name: string; provider: string; config: Record<string, unknown>};
type SecurityData = {
    username: string | null;
    roles: string[];
    effectiveRoles?: string[];
    firewallName: string | null;
    authenticated: boolean;
    token?: TokenInfo | null;
    impersonation?: ImpersonationInfo | null;
    guards?: GuardInfo[];
    roleHierarchy?: Record<string, string[]>;
    authenticationEvents?: AuthenticationEvent[];
    accessDecisions: AccessDecision[];
};
type SecurityPanelProps = {data: SecurityData};

const InfoCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2.5),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    marginBottom: theme.spacing(3),
}));

const FieldRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    marginBottom: theme.spacing(1),
    fontSize: '13px',
}));

const FieldLabel = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    fontWeight: 600,
    color: theme.palette.text.disabled,
    width: 100,
    flexShrink: 0,
}));

const DecisionRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const AttributeCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    minWidth: 0,
});

const SubjectCell = styled(Typography)(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.secondary,
    flexShrink: 0,
    maxWidth: 200,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const ImpersonationBanner = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1.5, 2),
    borderRadius: theme.shape.borderRadius,
    backgroundColor: theme.palette.warning.main,
    color: theme.palette.warning.contrastText,
    marginBottom: theme.spacing(2),
    fontSize: '13px',
    fontWeight: 500,
}));

const EventRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(0.75, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
    '&:last-child': {borderBottom: 'none'},
}));

const DetailLabel = ({children}: {children: React.ReactNode}) => (
    <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>{children}</Typography>
);

export const SecurityPanel = ({data}: SecurityPanelProps) => {
    const theme = useTheme();
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data) {
        return <EmptyState icon="security" title="No security data found" />;
    }

    const grantedCount = data.accessDecisions?.filter((d) => d.result.includes('GRANTED')).length ?? 0;
    const deniedCount = (data.accessDecisions?.length ?? 0) - grantedCount;

    return (
        <Box>
            {data.impersonation && (
                <ImpersonationBanner>
                    <Icon sx={{fontSize: 18}}>swap_horiz</Icon>
                    Impersonating {data.impersonation.impersonatedUser} (original: {data.impersonation.originalUser})
                </ImpersonationBanner>
            )}

            <InfoCard>
                <FieldRow>
                    <FieldLabel>Username</FieldLabel>
                    <Typography sx={{fontSize: '13px', fontWeight: 500}}>{data.username ?? 'Anonymous'}</Typography>
                </FieldRow>
                <FieldRow>
                    <FieldLabel>Status</FieldLabel>
                    <Chip
                        label={data.authenticated ? 'Authenticated' : 'Not Authenticated'}
                        size="small"
                        sx={{
                            fontWeight: 600,
                            fontSize: '10px',
                            height: 20,
                            borderRadius: 1,
                            backgroundColor: data.authenticated ? theme.palette.success.main : theme.palette.error.main,
                            color: 'common.white',
                        }}
                    />
                </FieldRow>
                {data.firewallName && (
                    <FieldRow>
                        <FieldLabel>Firewall</FieldLabel>
                        <Typography
                            sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px', color: 'text.secondary'}}
                        >
                            {data.firewallName}
                        </Typography>
                    </FieldRow>
                )}
                {data.roles.length > 0 && (
                    <FieldRow>
                        <FieldLabel>Roles</FieldLabel>
                        <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                            {data.roles.map((role) => (
                                <Chip
                                    key={role}
                                    label={role}
                                    size="small"
                                    variant="outlined"
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '10px',
                                        height: 20,
                                        borderRadius: 1,
                                    }}
                                />
                            ))}
                        </Box>
                    </FieldRow>
                )}
                {data.effectiveRoles && data.effectiveRoles.length > data.roles.length && (
                    <FieldRow>
                        <FieldLabel>Effective</FieldLabel>
                        <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                            {data.effectiveRoles
                                .filter((r) => !data.roles.includes(r))
                                .map((role) => (
                                    <Chip
                                        key={role}
                                        label={role}
                                        size="small"
                                        variant="outlined"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            height: 20,
                                            borderRadius: 1,
                                            borderStyle: 'dashed',
                                            color: 'text.secondary',
                                        }}
                                    />
                                ))}
                        </Box>
                    </FieldRow>
                )}
                {data.token && (
                    <FieldRow>
                        <FieldLabel>Token</FieldLabel>
                        <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                            <Chip
                                label={data.token.type.toUpperCase()}
                                size="small"
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '10px',
                                    fontWeight: 600,
                                    height: 20,
                                    borderRadius: 1,
                                }}
                            />
                            {data.token.expiresAt && (
                                <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>
                                    expires {data.token.expiresAt}
                                </Typography>
                            )}
                        </Box>
                    </FieldRow>
                )}
            </InfoCard>

            {data.authenticationEvents && data.authenticationEvents.length > 0 && (
                <Box sx={{mb: 3}}>
                    <SectionTitle>{`Authentication Events (${data.authenticationEvents.length})`}</SectionTitle>
                    <Box
                        sx={{
                            border: `1px solid ${theme.palette.divider}`,
                            borderRadius: theme.shape.borderRadius * 1.5,
                            overflow: 'hidden',
                        }}
                    >
                        {data.authenticationEvents.map((event, index) => (
                            <EventRow key={index}>
                                <Chip
                                    label={event.result}
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 55,
                                        borderRadius: 1,
                                        backgroundColor:
                                            event.result === 'success'
                                                ? theme.palette.success.main
                                                : theme.palette.error.main,
                                        color: 'common.white',
                                        flexShrink: 0,
                                    }}
                                />
                                <Typography
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '12px',
                                        fontWeight: 500,
                                        flex: 1,
                                    }}
                                >
                                    {event.type}
                                </Typography>
                                <Typography
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '11px',
                                        color: 'text.secondary',
                                    }}
                                >
                                    {event.provider}
                                </Typography>
                            </EventRow>
                        ))}
                    </Box>
                </Box>
            )}

            {data.accessDecisions && data.accessDecisions.length > 0 && (
                <Box>
                    <SectionTitle>{`${data.accessDecisions.length} access decisions (${grantedCount} granted, ${deniedCount} denied)`}</SectionTitle>

                    {data.accessDecisions.map((decision, index) => {
                        const expanded = expandedIndex === index;
                        const isGranted = decision.result.includes('GRANTED');
                        return (
                            <Box key={index}>
                                <DecisionRow
                                    expanded={expanded}
                                    onClick={() => setExpandedIndex(expanded ? null : index)}
                                >
                                    <Chip
                                        label={decision.result}
                                        size="small"
                                        sx={{
                                            fontWeight: 700,
                                            fontSize: '9px',
                                            height: 18,
                                            minWidth: 60,
                                            borderRadius: 1,
                                            backgroundColor: isGranted
                                                ? theme.palette.success.main
                                                : theme.palette.error.main,
                                            color: 'common.white',
                                            flexShrink: 0,
                                        }}
                                    />
                                    <AttributeCell>{decision.attribute}</AttributeCell>
                                    {decision.duration != null && (
                                        <Typography
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '10px',
                                                color: 'text.disabled',
                                                flexShrink: 0,
                                            }}
                                        >
                                            {(decision.duration * 1000).toFixed(1)}ms
                                        </Typography>
                                    )}
                                    <SubjectCell>{decision.subject}</SubjectCell>
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </DecisionRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <Box sx={{mb: 1}}>
                                            <DetailLabel>Subject</DetailLabel>
                                            <Typography
                                                sx={{
                                                    fontFamily: primitives.fontFamilyMono,
                                                    fontSize: '12px',
                                                    color: 'text.secondary',
                                                    wordBreak: 'break-word',
                                                }}
                                            >
                                                {decision.subject}
                                            </Typography>
                                        </Box>
                                        {decision.voters.length > 0 && (
                                            <Box sx={{mb: 1}}>
                                                <DetailLabel>Voters</DetailLabel>
                                                <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                                                    {decision.voters.map((voter, i) => (
                                                        <Chip
                                                            key={i}
                                                            label={
                                                                typeof voter === 'string'
                                                                    ? voter
                                                                    : `${voter.voter}: ${voter.result}`
                                                            }
                                                            size="small"
                                                            variant="outlined"
                                                            sx={{
                                                                fontFamily: primitives.fontFamilyMono,
                                                                fontSize: '10px',
                                                                height: 20,
                                                                borderRadius: 1,
                                                            }}
                                                        />
                                                    ))}
                                                </Box>
                                            </Box>
                                        )}
                                        {decision.context && Object.keys(decision.context).length > 0 && (
                                            <Box>
                                                <DetailLabel>Context</DetailLabel>
                                                <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                                                    {Object.entries(decision.context).map(([key, value]) => (
                                                        <Chip
                                                            key={key}
                                                            label={`${key}: ${String(value)}`}
                                                            size="small"
                                                            variant="outlined"
                                                            sx={{
                                                                fontFamily: primitives.fontFamilyMono,
                                                                fontSize: '10px',
                                                                height: 20,
                                                                borderRadius: 1,
                                                            }}
                                                        />
                                                    ))}
                                                </Box>
                                            </Box>
                                        )}
                                    </DetailBox>
                                </Collapse>
                            </Box>
                        );
                    })}
                </Box>
            )}
        </Box>
    );
};
