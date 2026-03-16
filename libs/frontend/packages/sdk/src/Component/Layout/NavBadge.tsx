import {styled} from '@mui/material/styles';

type NavBadgeProps = {count: number | string; variant?: 'default' | 'error'};

const BadgeRoot = styled('span', {shouldForwardProp: (prop) => prop !== 'variant'})<{variant: 'default' | 'error'}>(
    ({theme, variant}) => ({
        fontSize: '10px',
        fontWeight: 600,
        minWidth: 18,
        height: 18,
        borderRadius: 9,
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '0 5px',
        fontFamily: theme.typography.fontFamily,
        ...(variant === 'error'
            ? {backgroundColor: theme.palette.error.light, color: theme.palette.error.main}
            : {backgroundColor: theme.palette.action.selected, color: theme.palette.text.secondary}),
    }),
);

export const NavBadge = ({count, variant = 'default'}: NavBadgeProps) => {
    if (count === 0 || count === '0') return null;
    return <BadgeRoot variant={variant}>{count}</BadgeRoot>;
};
