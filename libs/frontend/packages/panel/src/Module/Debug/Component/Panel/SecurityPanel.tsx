import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useState} from 'react';

type AccessDecision = {attribute: string; subject: string; result: string; voters: string[]};
type SecurityData = {
    username: string | null;
    roles: string[];
    firewallName: string | null;
    authenticated: boolean;
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
    width: 80,
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

export const SecurityPanel = ({data}: SecurityPanelProps) => {
    const theme = useTheme();
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data) {
        return <EmptyState icon="security" title="No security data found" />;
    }

    const grantedCount = data.accessDecisions?.filter((d) => d.result === 'GRANTED').length ?? 0;
    const deniedCount = data.accessDecisions?.filter((d) => d.result === 'DENIED').length ?? 0;

    return (
        <Box>
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
            </InfoCard>

            {data.accessDecisions && data.accessDecisions.length > 0 && (
                <Box>
                    <SectionTitle>{`${data.accessDecisions.length} access decisions (${grantedCount} granted, ${deniedCount} denied)`}</SectionTitle>

                    {data.accessDecisions.map((decision, index) => {
                        const expanded = expandedIndex === index;
                        const isGranted = decision.result === 'GRANTED';
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
                                    <SubjectCell>{decision.subject}</SubjectCell>
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </DecisionRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <Box sx={{mb: 1}}>
                                            <Typography
                                                sx={{
                                                    fontSize: '11px',
                                                    fontWeight: 600,
                                                    color: 'text.disabled',
                                                    mb: 0.5,
                                                }}
                                            >
                                                Subject
                                            </Typography>
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
                                            <Box>
                                                <Typography
                                                    sx={{
                                                        fontSize: '11px',
                                                        fontWeight: 600,
                                                        color: 'text.disabled',
                                                        mb: 0.5,
                                                    }}
                                                >
                                                    Voters
                                                </Typography>
                                                <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                                                    {decision.voters.map((voter, i) => (
                                                        <Chip
                                                            key={i}
                                                            label={voter}
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
