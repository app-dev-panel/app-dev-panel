import {Icon, IconButton} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';

type SearchTriggerProps = {onClick?: () => void};

const TriggerRoot = styled('button')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    padding: theme.spacing(0.75, 1.75),
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.default,
    color: theme.palette.text.disabled,
    cursor: 'pointer',
    fontSize: '13px',
    fontFamily: theme.typography.fontFamily,
    '&:hover': {borderColor: theme.palette.primary.main},
}));

const Kbd = styled('kbd')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '10px',
    backgroundColor: theme.palette.background.paper,
    padding: '1px 5px',
    borderRadius: 3,
    border: `1px solid ${theme.palette.divider}`,
}));

export const SearchTrigger = ({onClick}: SearchTriggerProps) => {
    const theme = useTheme();
    const compact = useMediaQuery(theme.breakpoints.down('md'));

    if (compact) {
        return (
            <IconButton size="small" onClick={onClick} aria-label="Search">
                <Icon sx={{fontSize: 18}}>search</Icon>
            </IconButton>
        );
    }

    return (
        <TriggerRoot onClick={onClick} aria-label="Search">
            <Icon sx={{fontSize: 16}}>search</Icon>
            Search...
            <Kbd>Ctrl+K</Kbd>
        </TriggerRoot>
    );
};
