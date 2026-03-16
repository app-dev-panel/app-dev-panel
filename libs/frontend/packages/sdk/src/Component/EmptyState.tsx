import {Box, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

type EmptyStateProps = {icon?: string; title: string; description?: string};

const Root = styled(Box)(({theme}) => ({
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    padding: theme.spacing(6, 2),
    textAlign: 'center',
}));

export const EmptyState = ({icon = 'inbox', title, description}: EmptyStateProps) => (
    <Root>
        <Icon sx={{fontSize: 48, color: 'text.disabled', mb: 2}}>{icon}</Icon>
        <Typography sx={{fontSize: '14px', fontWeight: 600, color: 'text.secondary', mb: 0.5}}>{title}</Typography>
        {description && (
            <Typography sx={{fontSize: '13px', color: 'text.disabled', maxWidth: 360}}>{description}</Typography>
        )}
    </Root>
);
