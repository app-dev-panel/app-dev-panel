import {NavBadge} from '@app-dev-panel/sdk/Component/Layout/NavBadge';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Icon} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type NavItemProps = {
    icon: string;
    label: string;
    badge?: number | string;
    badgeVariant?: 'default' | 'error';
    active?: boolean;
    onClick?: () => void;
    href?: string;
};

const NavItemRoot = styled('div', {shouldForwardProp: (prop) => prop !== 'active'})<{active: boolean}>(
    ({theme, active}) => ({
        height: componentTokens.navItem.height,
        borderRadius: componentTokens.navItem.borderRadius,
        display: 'flex',
        alignItems: 'center',
        padding: theme.spacing(0, 1.25),
        gap: theme.spacing(1.25),
        cursor: 'pointer',
        color: theme.palette.text.secondary,
        transition: 'all 0.12s ease',
        whiteSpace: 'nowrap',
        flexShrink: 0,
        position: 'relative',
        userSelect: 'none',
        '&:hover': {backgroundColor: theme.palette.action.hover, color: theme.palette.text.primary},
        ...(active && {
            backgroundColor: theme.palette.primary.light,
            color: theme.palette.primary.main,
            fontWeight: 500,
            '&::before': {
                content: '""',
                position: 'absolute',
                left: 0,
                top: 8,
                bottom: 8,
                width: componentTokens.navItem.activeBarWidth,
                backgroundColor: theme.palette.primary.main,
                borderRadius: '0 2px 2px 0',
            },
        }),
    }),
);

const NavLabel = styled('span')(({theme}) => ({fontSize: theme.typography.body2.fontSize}));

const BadgeSlot = styled('span')({marginLeft: 'auto'});

export const NavItem = React.memo(({icon, label, badge, badgeVariant, active = false, onClick}: NavItemProps) => {
    return (
        <NavItemRoot active={active} onClick={onClick}>
            <Icon sx={{fontSize: 19, flexShrink: 0}}>{icon}</Icon>
            <NavLabel>{label}</NavLabel>
            {badge !== undefined && badge !== 0 && (
                <BadgeSlot>
                    <NavBadge count={badge} variant={badgeVariant} />
                </BadgeSlot>
            )}
        </NavItemRoot>
    );
});
