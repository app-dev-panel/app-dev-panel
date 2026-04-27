import {PanelBreadcrumbInline, usePanelBreadcrumb} from '@app-dev-panel/sdk/Component/PanelBreadcrumb';
import {Box} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type PageToolbarProps = {
    children: React.ReactNode;
    actions?: React.ReactNode;
    sticky?: boolean;
};

const Root = styled(Box, {shouldForwardProp: (p) => p !== 'sticky'})<{sticky?: boolean}>(({theme, sticky}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(2),
    padding: theme.spacing(1.25, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    [theme.breakpoints.up('sm')]: {padding: theme.spacing(1.5, 2.5)},
    ...(sticky && {position: 'sticky', top: 0, zIndex: 2}),
}));

const Label = styled('div')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    minWidth: 0,
    fontSize: theme.typography.overline.fontSize,
    fontWeight: theme.typography.overline.fontWeight,
    letterSpacing: '0.05em',
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

const Actions = styled(Box)(({theme}) => ({
    marginLeft: 'auto',
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    flexShrink: 0,
}));

export const PageToolbar = ({children, actions, sticky}: PageToolbarProps) => {
    const breadcrumb = usePanelBreadcrumb();
    return (
        <Root sticky={sticky}>
            <Label>
                {sticky && breadcrumb && <PanelBreadcrumbInline label={breadcrumb} />}
                {children}
            </Label>
            {actions && <Actions>{actions}</Actions>}
        </Root>
    );
};
