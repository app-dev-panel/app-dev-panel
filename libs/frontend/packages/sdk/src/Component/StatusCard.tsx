import {Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

type StatusCardProps = {
    title: string;
    icon: string;
    status: 'connected' | 'disconnected' | 'loading';
    onClick?: () => void;
};

const Root = styled('div', {shouldForwardProp: (p) => p !== 'status'})<{status: StatusCardProps['status']}>(
    ({theme, status}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(2),
        padding: theme.spacing(2, 2.5),
        borderRadius: theme.shape.borderRadius * 1.5,
        border: `1px solid ${theme.palette.divider}`,
        backgroundColor: theme.palette.background.paper,
        cursor: 'pointer',
        transition: 'all 0.2s',
        ...(status === 'connected' && {borderLeftColor: theme.palette.success.main, borderLeftWidth: 3}),
        ...(status === 'disconnected' && {borderLeftColor: theme.palette.error.main, borderLeftWidth: 3}),
        '&:hover': {boxShadow: '0 4px 12px rgba(0,0,0,0.08)', transform: 'translateY(-1px)'},
    }),
);

const IconBox = styled('div', {shouldForwardProp: (p) => p !== 'status'})<{status: StatusCardProps['status']}>(
    ({theme, status}) => ({
        width: 40,
        height: 40,
        borderRadius: theme.shape.borderRadius,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor:
            status === 'connected'
                ? theme.palette.success.light || '#ECFDF5'
                : status === 'disconnected'
                  ? theme.palette.error.light || '#FEF2F2'
                  : theme.palette.action.hover,
    }),
);

const StatusDot = styled('span', {shouldForwardProp: (p) => p !== 'status'})<{status: StatusCardProps['status']}>(
    ({theme, status}) => ({
        width: 8,
        height: 8,
        borderRadius: '50%',
        backgroundColor:
            status === 'connected'
                ? theme.palette.success.main
                : status === 'disconnected'
                  ? theme.palette.error.main
                  : theme.palette.text.disabled,
        ...(status === 'loading' && {animation: 'pulse 1.5s ease-in-out infinite'}),
        '@keyframes pulse': {'0%, 100%': {opacity: 1}, '50%': {opacity: 0.3}},
    }),
);

export const StatusCard = ({title, icon, status, onClick}: StatusCardProps) => {
    const iconColor =
        status === 'connected' ? 'success.main' : status === 'disconnected' ? 'error.main' : 'text.disabled';

    return (
        <Root status={status} onClick={onClick}>
            <IconBox status={status}>
                <Icon sx={{fontSize: 22, color: iconColor}}>{icon}</Icon>
            </IconBox>
            <div style={{flex: 1}}>
                <Typography sx={{fontWeight: 600, fontSize: '14px'}}>{title}</Typography>
                <Typography sx={{fontSize: '12px', color: 'text.secondary', textTransform: 'capitalize'}}>
                    {status}
                </Typography>
            </div>
            <StatusDot status={status} />
        </Root>
    );
};
