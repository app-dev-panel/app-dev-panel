import {Icon} from '@mui/material';
import {styled} from '@mui/material/styles';

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
    return (
        <TriggerRoot onClick={onClick}>
            <Icon sx={{fontSize: 16}}>search</Icon>
            Search...
            <Kbd>Ctrl+K</Kbd>
        </TriggerRoot>
    );
};
