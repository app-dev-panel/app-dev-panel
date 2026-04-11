import {Box, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import type {ReactNode} from 'react';

type EmptyStateProps = {
    icon?: string;
    title: string;
    description?: ReactNode;
    action?: ReactNode;
    severity?: 'default' | 'error';
};

const Root = styled(Box)(({theme}) => ({
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    padding: theme.spacing(6, 2),
    textAlign: 'center',
}));

export const EmptyState = ({icon = 'inbox', title, description, action, severity = 'default'}: EmptyStateProps) => {
    const iconColor = severity === 'error' ? 'error.main' : 'text.disabled';
    const titleColor = severity === 'error' ? 'error.main' : 'text.secondary';
    return (
        <Root>
            <Icon sx={{fontSize: 48, color: iconColor, mb: 2}}>{icon}</Icon>
            <Typography sx={{fontSize: '14px', fontWeight: 600, color: titleColor, mb: 0.5}}>{title}</Typography>
            {description && (
                <Typography sx={{fontSize: '13px', color: 'text.disabled', maxWidth: 360}}>{description}</Typography>
            )}
            {action && <Box sx={{mt: 2}}>{action}</Box>}
        </Root>
    );
};
